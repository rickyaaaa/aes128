@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <section class="panel overflow-hidden">
        <div class="border-b border-zinc-200 bg-zinc-950 px-6 py-7 text-white">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Yokprinting</p>
            <h2 class="mt-2 text-2xl font-bold">Tentang Aplikasi Ini</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-300">
                Yokprinting File Security adalah aplikasi internal perusahaan untuk melindungi file JPG, PNG, dan PDF menggunakan enkripsi
                AES-128-CBC. Owner dan staff dapat mengelola file terenkripsi sesuai hak akses masing-masing.
            </p>
        </div>
    </section>

    <section class="panel p-6">
        <h2 class="text-xl font-bold">Fitur Terkini</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <h3 class="font-bold">Enkripsi File</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600">Mengamankan file dengan kata sandi dinamis untuk setiap proses.</p>
            </article>
            <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <h3 class="font-bold">Dekripsi Langsung</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600">Mengunduh file asli setelah kata sandi berhasil diverifikasi.</p>
            </article>
            <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <h3 class="font-bold">Detail File</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600">Menampilkan nama, ukuran, ekstensi, pemilik, alamat IP, dan waktu unggah WIB.</p>
            </article>
            <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <h3 class="font-bold">Riwayat Dinamis</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600">Memfilter repository file berdasarkan ekstensi dan rentang tanggal.</p>
            </article>
            <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <h3 class="font-bold">Update Kata Sandi</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600">Staff dapat mengganti kata sandi untuk file miliknya sendiri.</p>
            </article>
            <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <h3 class="font-bold">Manajemen Staff</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600">Owner dapat membuat, memperbarui, dan menghapus akun staff.</p>
            </article>
        </div>
    </section>

    <section class="panel p-6">
        <h2 class="text-xl font-bold">Alur Kerja Sistem</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['number' => '01', 'title' => 'Login', 'description' => 'Masuk menggunakan akun owner atau staff aktif.'],
                ['number' => '02', 'title' => 'Unggah', 'description' => 'Pilih file lalu masukkan kata sandi minimal delapan karakter.'],
                ['number' => '03', 'title' => 'Enkripsi', 'description' => 'Server mengenkripsi file dan menyimpan versi .enc di storage.'],
                ['number' => '04', 'title' => 'Dekripsi', 'description' => 'Buka detail file dan masukkan kata sandi untuk mengunduh file asli.'],
            ] as $step)
                <article class="rounded-lg border border-zinc-200 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $step['number'] }}</p>
                    <h3 class="mt-2 font-bold">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-zinc-600">{{ $step['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="panel p-6">
        <h2 class="text-xl font-bold">Teknologi Terkini</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['title' => 'Laravel 11', 'description' => 'Backend monolitik, routing, validasi, middleware, dan autentikasi sesi.'],
                ['title' => 'Blade', 'description' => 'Template server-side untuk dashboard, detail file, dan audit log.'],
                ['title' => 'Tailwind CSS', 'description' => 'Antarmuka responsif dengan komponen visual yang konsisten.'],
                ['title' => 'SQLite', 'description' => 'Penyimpanan metadata file, akun, sesi, dan riwayat proses.'],
            ] as $technology)
                <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                    <h3 class="font-bold">{{ $technology['title'] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-zinc-600">{{ $technology['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="panel p-6">
        <h2 class="text-xl font-bold">Spesifikasi Keamanan</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_0.9fr]">
            <ul class="grid gap-3 text-sm text-zinc-700 sm:grid-cols-2">
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">Algoritma enkripsi: OpenSSL AES-128-CBC.</li>
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">Derivasi kunci: PBKDF2 SHA-256 dengan salt acak.</li>
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">Integrity check: HMAC SHA-256 untuk payload terenkripsi.</li>
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">IV dibuat secara acak untuk setiap file terenkripsi.</li>
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">Kata sandi tidak disimpan di database.</li>
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">File hasil dekripsi tidak disimpan kembali di server.</li>
            </ul>
            <div class="rounded-lg border border-zinc-200 p-5">
                <h3 class="font-bold">Hak Akses</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="font-semibold text-zinc-900">Owner</dt>
                        <dd class="mt-1 leading-6 text-zinc-600">Memantau seluruh file, mendekripsi file staff, menghapus record, dan mengelola akun staff.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-zinc-900">Staff</dt>
                        <dd class="mt-1 leading-6 text-zinc-600">Mengunggah, mendekripsi, dan mengganti kata sandi untuk file miliknya sendiri.</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="panel p-6">
        <h2 class="text-xl font-bold">Panduan Cepat</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
            <ol class="grid gap-3 text-sm text-zinc-700 sm:grid-cols-2">
                <li class="rounded-lg border border-zinc-200 p-4"><span class="font-bold">1.</span> Buka halaman Enkripsi dari menu navigasi.</li>
                <li class="rounded-lg border border-zinc-200 p-4"><span class="font-bold">2.</span> Pilih file JPG, PNG, atau PDF dengan ukuran maksimal 20 MB.</li>
                <li class="rounded-lg border border-zinc-200 p-4"><span class="font-bold">3.</span> Masukkan kata sandi minimal delapan karakter lalu tekan Amankan File.</li>
                <li class="rounded-lg border border-zinc-200 p-4"><span class="font-bold">4.</span> Gunakan halaman detail atau riwayat untuk mendekripsi file.</li>
            </ol>
            <a href="{{ route('files.encrypt') }}" class="primary-button">Mulai Enkripsi</a>
        </div>
    </section>
</div>
@endsection
