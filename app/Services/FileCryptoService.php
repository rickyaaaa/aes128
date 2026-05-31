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

    public function encryptUploadedFile(UploadedFile $file, string $passphrase): array
    {
        $plainBytes = file_get_contents($file->getRealPath());
        if ($plainBytes === false) {
            throw new RuntimeException('Gagal membaca file sumber.');
        }

        $encryptedBytes = $this->encryptBytes($plainBytes, $passphrase, $file->getClientOriginalName(), strtolower($file->getClientOriginalExtension()));
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

    public function rotatePassphrase(FileLog $fileLog, string $oldPassphrase, string $newPassphrase): void
    {
        $decrypted = $this->decryptFromLog($fileLog, $oldPassphrase);

        $reEncrypted = $this->encryptBytes(
            $decrypted['plain_bytes'],
            $newPassphrase,
            $decrypted['original_file_name'],
            $decrypted['original_file_type'],
        );

        Storage::disk('local')->put($fileLog->stored_path, $reEncrypted);
    }

    private function encryptBytes(string $plainBytes, string $passphrase, string $originalFileName, string $originalFileType): string
    {
        $salt = random_bytes(16);
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encryptionKey = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITERATIONS, self::KEY_LENGTH, true);
        $macKey = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITERATIONS, 32, true);

        $ciphertext = openssl_encrypt($plainBytes, self::CIPHER, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new RuntimeException('OpenSSL gagal melakukan enkripsi.');
        }

        $mac = hash_hmac('sha256', $iv.$ciphertext, $macKey, true);

        $payload = [
            'version' => 1,
            'cipher' => self::CIPHER,
            'iter' => self::PBKDF2_ITERATIONS,
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'mac' => base64_encode($mac),
            'file_name' => $originalFileName,
            'file_type' => $originalFileType,
            'ciphertext' => base64_encode($ciphertext),
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function decryptBytes(string $encryptedBytes, string $passphrase): array
    {
        try {
            $payload = json_decode($encryptedBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidSecretKeyException('Invalid password');
        }

        foreach (['cipher', 'iter', 'salt', 'iv', 'mac', 'file_name', 'file_type', 'ciphertext'] as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new InvalidSecretKeyException('Invalid password');
            }
        }

        if (($payload['cipher'] ?? null) !== self::CIPHER) {
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
            'original_file_name' => (string) $payload['file_name'],
            'original_file_type' => (string) $payload['file_type'],
        ];
    }
}
