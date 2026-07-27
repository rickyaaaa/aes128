<?php

namespace App\Services;

use App\Exceptions\InvalidSecretKeyException;
use App\Models\FileLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileCryptoService
{
    private const CIPHER = 'aes-128-cbc';

    private const PBKDF2_ITERATIONS = 120000;

    private const KEY_LENGTH = 16;

    private const SALT_LENGTH = 16;

    private const MAC_LENGTH = 32;

    private const MAGIC = 'AESF';

    private const LEGACY_SIMPLE_CIPHER = 'AES-128-CBC';

    public function encryptUploadedFile(UploadedFile $file, string $passphrase): array
    {
        $plainBytes = file_get_contents($file->getRealPath());
        if ($plainBytes === false) {
            throw new RuntimeException('Gagal membaca file sumber.');
        }

        $encryptedBytes = $this->encryptBytes($plainBytes, $passphrase, $file->getClientOriginalName());
        $storedPath = 'encrypted/'.date('Y/m').'/'.Str::uuid()->toString().'.enc';

        Storage::disk('local')->put($storedPath, $encryptedBytes);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => (int) $file->getSize(),
            'stored_path' => $storedPath,
        ];
    }

    public function decryptFromLog(FileLog $fileLog, string $passphrase): array
    {
        if (! str_ends_with(strtolower($fileLog->stored_path), '.enc')) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        if (! Storage::disk('local')->exists($fileLog->stored_path)) {
            throw new RuntimeException('File terenkripsi tidak ditemukan di storage.');
        }

        $encryptedBytes = Storage::disk('local')->get($fileLog->stored_path);

        return $this->decryptBytes($encryptedBytes, $passphrase);
    }

    public function decryptUploadedFile(UploadedFile $file, string $passphrase): array
    {
        $encryptedBytes = file_get_contents($file->getRealPath());
        if ($encryptedBytes === false) {
            throw new RuntimeException('Gagal membaca file terenkripsi.');
        }

        return $this->decryptBytes($encryptedBytes, $passphrase);
    }

    public function rotatePassphrase(FileLog $fileLog, string $oldPassphrase, string $newPassphrase): void
    {
        $decrypted = $this->decryptFromLog($fileLog, $oldPassphrase);

        $reEncrypted = $this->encryptBytes(
            $decrypted['plain_bytes'],
            $newPassphrase,
            $decrypted['original_file_name'],
        );

        Storage::disk('local')->put($fileLog->stored_path, $reEncrypted);
    }

    private function encryptBytes(string $plainBytes, string $passphrase, string $originalFileName): string
    {
        if (strlen($originalFileName) > 65535) {
            throw new RuntimeException('Nama file terlalu panjang.');
        }

        // Nama file asli ikut dienkripsi (bukan disimpan sebagai metadata plaintext)
        // supaya isi .enc tetap opaque, tapi tetap bisa dipulihkan begitu password benar.
        $payloadPlain = pack('n', strlen($originalFileName)).$originalFileName.$plainBytes;

        $salt = random_bytes(self::SALT_LENGTH);
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encryptionKey = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITERATIONS, self::KEY_LENGTH, true);
        $macKey = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITERATIONS, 32, true);

        $ciphertext = openssl_encrypt($payloadPlain, self::CIPHER, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new RuntimeException('OpenSSL gagal melakukan enkripsi.');
        }

        $mac = hash_hmac('sha256', $iv.$ciphertext, $macKey, true);

        return self::MAGIC.$salt.$iv.$mac.$ciphertext;
    }

    private function decryptBytes(string $encryptedBytes, string $passphrase): array
    {
        if (! str_starts_with($encryptedBytes, self::MAGIC)) {
            return $this->decryptLegacyJsonBytes($encryptedBytes, $passphrase);
        }

        return $this->decryptCurrentBytes($encryptedBytes, $passphrase);
    }

    private function decryptCurrentBytes(string $encryptedBytes, string $passphrase): array
    {
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $headerLength = strlen(self::MAGIC) + self::SALT_LENGTH + $ivLength + self::MAC_LENGTH;

        if (strlen($encryptedBytes) < $headerLength || ! str_starts_with($encryptedBytes, self::MAGIC)) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $offset = strlen(self::MAGIC);
        $salt = substr($encryptedBytes, $offset, self::SALT_LENGTH);
        $offset += self::SALT_LENGTH;
        $iv = substr($encryptedBytes, $offset, $ivLength);
        $offset += $ivLength;
        $mac = substr($encryptedBytes, $offset, self::MAC_LENGTH);
        $offset += self::MAC_LENGTH;
        $ciphertext = substr($encryptedBytes, $offset);

        $encryptionKey = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITERATIONS, self::KEY_LENGTH, true);
        $macKey = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITERATIONS, 32, true);
        $expectedMac = hash_hmac('sha256', $iv.$ciphertext, $macKey, true);

        if (! hash_equals($expectedMac, $mac)) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $payloadPlain = openssl_decrypt($ciphertext, self::CIPHER, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        if ($payloadPlain === false || strlen($payloadPlain) < 2) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $nameLength = unpack('n', substr($payloadPlain, 0, 2))[1];
        if (strlen($payloadPlain) < 2 + $nameLength) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        return [
            'plain_bytes' => substr($payloadPlain, 2 + $nameLength),
            'original_file_name' => substr($payloadPlain, 2, $nameLength),
        ];
    }

    private function decryptLegacyJsonBytes(string $encryptedBytes, string $passphrase): array
    {
        try {
            $payload = json_decode($encryptedBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        if (! is_array($payload)) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        if (array_key_exists('cipher', $payload)) {
            return $this->decryptLegacyPbkdf2Json($payload, $passphrase);
        }

        if (array_key_exists('algorithm', $payload)) {
            return $this->decryptLegacySimpleJson($payload, $passphrase);
        }

        throw new InvalidSecretKeyException('Invalid password');
    }

    private function decryptLegacyPbkdf2Json(array $payload, string $passphrase): array
    {
        foreach (['cipher', 'iter', 'salt', 'iv', 'mac', 'file_name', 'file_type', 'ciphertext'] as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new InvalidSecretKeyException('Invalid password');
            }
        }

        if ((string) $payload['cipher'] !== self::CIPHER) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $salt = base64_decode((string) $payload['salt'], true);
        $iv = base64_decode((string) $payload['iv'], true);
        $mac = base64_decode((string) $payload['mac'], true);
        $ciphertext = base64_decode((string) $payload['ciphertext'], true);

        if ($salt === false || $iv === false || $mac === false || $ciphertext === false) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $iterations = (int) $payload['iter'];
        if ($iterations <= 0) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $encryptionKey = hash_pbkdf2('sha256', $passphrase, $salt, $iterations, self::KEY_LENGTH, true);
        $macKey = hash_pbkdf2('sha256', $passphrase, $salt, $iterations, 32, true);
        $expectedMac = hash_hmac('sha256', $iv.$ciphertext, $macKey, true);

        if (! hash_equals($expectedMac, $mac)) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $plainBytes = openssl_decrypt($ciphertext, self::CIPHER, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        if ($plainBytes === false) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        return [
            'plain_bytes' => $plainBytes,
            'original_file_name' => $this->sanitizeFilename((string) $payload['file_name']),
            'original_file_type' => (string) $payload['file_type'],
        ];
    }

    private function decryptLegacySimpleJson(array $payload, string $passphrase): array
    {
        foreach (['version', 'algorithm', 'original_filename', 'file_type', 'iv', 'ciphertext', 'mac'] as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new InvalidSecretKeyException('Invalid password');
            }
        }

        if ((int) $payload['version'] !== 1 || (string) $payload['algorithm'] !== self::LEGACY_SIMPLE_CIPHER) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $iv = base64_decode((string) $payload['iv'], true);
        $ciphertext = base64_decode((string) $payload['ciphertext'], true);

        if ($iv === false || $ciphertext === false) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $expectedMac = hash_hmac('sha256', $iv.$ciphertext, $this->legacySimpleMacKey($passphrase));

        if (! hash_equals($expectedMac, (string) $payload['mac'])) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        $plainBytes = openssl_decrypt(
            $ciphertext,
            self::LEGACY_SIMPLE_CIPHER,
            $this->legacySimpleEncryptionKey($passphrase),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($plainBytes === false) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        return [
            'plain_bytes' => $plainBytes,
            'original_file_name' => $this->sanitizeFilename((string) $payload['original_filename']),
            'original_file_type' => (string) $payload['file_type'],
        ];
    }

    private function legacySimpleEncryptionKey(string $passphrase): string
    {
        return substr(hash('sha256', 'aes128-encryption|'.$passphrase, true), 0, self::KEY_LENGTH);
    }

    private function legacySimpleMacKey(string $passphrase): string
    {
        return hash('sha256', 'aes128-mac|'.$passphrase, true);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));

        return preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'decrypted-file';
    }
}
