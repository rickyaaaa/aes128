# Yokprinting File Security

Laravel 11 monolith based on `prd (3).md`.

## Implementasi Saat Ini

- Laravel Blade + Tailwind CSS (Vite).
- Session authentication dengan role `owner` dan `staff`.
- Role middleware untuk pemisahan hak akses.
- Manajemen user staff hanya oleh owner (closed system, tanpa registrasi publik).
- Enkripsi file dengan OpenSSL `aes-128-cbc` menggunakan passphrase dinamis (minimal 8 karakter).
- Derivasi kunci dengan PBKDF2 + salt acak per file + IV acak per file.
- File terenkripsi disimpan sebagai `.enc` di Laravel local storage.
- Dekripsi dilakukan di memori dan langsung auto-download ke user (tanpa menyimpan plaintext di server).
- Owner dapat melihat semua file, filter ekstensi/tanggal, dan menghapus file/record.
- Staff hanya dapat mengakses file milik sendiri (download encrypted, decrypt, update passphrase).

## Route Utama

- `GET /login`
- `GET /owner/dashboard`
- `GET /staff/dashboard`
- `GET /encrypt`
- `GET /history`
- `GET /owner/users`

## Route Aksi File

- `POST /encrypt`
- `GET /file-logs/{fileLog}/download`
- `POST /file-logs/{fileLog}/decrypt`
- `POST /file-logs/{fileLog}/password`
- `DELETE /file-logs/{fileLog}`

## Akun Seed

- Owner: `owner` / `password`
- Staff: `staff` / `password`

## Local Development

```bash
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8010
```

## Verifikasi

```bash
php artisan test
npm run build
```
