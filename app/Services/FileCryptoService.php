<?php

namespace App\Services;

use App\Exceptions\InvalidSecretKeyException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileCryptoService
{
    private const CIPHER = 'AES-128-CBC';
    private const VERSION = 1;

    public function encrypt(UploadedFile $file, string $secretKey): array
    {
        $plainText = file_get_contents($file->getRealPath());

        if ($plainText === false) {
            throw new RuntimeException('File sumber tidak dapat dibaca.');
        }

        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $this->encryptionKey($secretKey),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($cipherText === false) {
            throw new RuntimeException('OpenSSL gagal mengenkripsi file.');
        }

        $payload = [
            'version' => self::VERSION,
            'algorithm' => self::CIPHER,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'iv' => base64_encode($iv),
            'ciphertext' => base64_encode($cipherText),
            'mac' => hash_hmac('sha256', $iv.$cipherText, $this->macKey($secretKey)),
        ];

        $outputFilename = $file->getClientOriginalName().'.enc';
        $storedPath = 'encrypted/'.Str::uuid().'.enc';

        Storage::disk('local')->put($storedPath, json_encode($payload, JSON_THROW_ON_ERROR));

        return [
            'status' => 'success',
            'output_filename' => $outputFilename,
            'stored_path' => $storedPath,
            'message' => 'File berhasil dienkripsi dengan AES-128-CBC.',
        ];
    }

    public function decrypt(UploadedFile $file, string $secretKey): array
    {
        $payload = $this->readPayload($file);
        $iv = base64_decode($payload['iv'], true);
        $cipherText = base64_decode($payload['ciphertext'], true);

        if ($iv === false || $cipherText === false) {
            throw new InvalidSecretKeyException('File terenkripsi tidak valid atau rusak.');
        }

        $expectedMac = hash_hmac('sha256', $iv.$cipherText, $this->macKey($secretKey));

        if (! hash_equals($expectedMac, $payload['mac'])) {
            throw new InvalidSecretKeyException('Kunci Rahasia salah atau file terenkripsi tidak cocok.');
        }

        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $this->encryptionKey($secretKey),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($plainText === false) {
            throw new InvalidSecretKeyException('Dekripsi gagal. Periksa Kunci Rahasia.');
        }

        $outputFilename = $this->sanitizeFilename($payload['original_filename']);
        $storedPath = 'decrypted/'.Str::uuid().'-'.$outputFilename;

        Storage::disk('local')->put($storedPath, $plainText);

        return [
            'status' => 'success',
            'output_filename' => $outputFilename,
            'stored_path' => $storedPath,
            'file_type' => $payload['file_type'],
            'message' => 'File berhasil didekripsi dengan AES-128-CBC.',
        ];
    }

    private function readPayload(UploadedFile $file): array
    {
        $rawPayload = file_get_contents($file->getRealPath());

        if ($rawPayload === false) {
            throw new RuntimeException('File .enc tidak dapat dibaca.');
        }

        try {
            $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidSecretKeyException('Format file .enc tidak valid.');
        }

        foreach (['version', 'algorithm', 'original_filename', 'file_type', 'iv', 'ciphertext', 'mac'] as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new InvalidSecretKeyException('Format file .enc tidak lengkap.');
            }
        }

        if ((int) $payload['version'] !== self::VERSION || $payload['algorithm'] !== self::CIPHER) {
            throw new InvalidSecretKeyException('Versi atau algoritma file .enc tidak didukung.');
        }

        if (! in_array($payload['file_type'], ['jpg', 'png', 'pdf'], true)) {
            throw new InvalidSecretKeyException('Tipe file asli dalam .enc tidak didukung.');
        }

        return $payload;
    }

    private function encryptionKey(string $secretKey): string
    {
        return substr(hash('sha256', 'aes128-encryption|'.$secretKey, true), 0, 16);
    }

    private function macKey(string $secretKey): string
    {
        return hash('sha256', 'aes128-mac|'.$secretKey, true);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));

        return preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'decrypted-file';
    }
}
