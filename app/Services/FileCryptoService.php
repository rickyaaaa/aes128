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
    private const MAGIC = "AESF";

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
}
