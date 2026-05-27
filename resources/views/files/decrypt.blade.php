@extends('layouts.app')

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <form method="POST" action="{{ route('files.decrypt.store') }}" enctype="multipart/form-data" class="panel p-5">
        @csrf
        <h2 class="text-lg font-bold">Ruang Buka</h2>
        <p class="mt-1 text-sm text-zinc-600">Input file .enc untuk dibuka kembali ke format asli.</p>

        <div class="mt-6 space-y-5">
            <label class="block">
                <span class="field-label">File terenkripsi</span>
                <input class="field-input" type="file" name="encrypted_file" accept=".enc">
                <span class="mt-2 block text-xs text-zinc-500">Hanya format .enc pada implementasi backend final.</span>
            </label>
            <label class="block">
                <span class="field-label">Kunci Rahasia</span>
                <input class="field-input" type="password" name="secret_key" placeholder="Gunakan kunci yang sama">
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="primary-button" type="submit">Buka File</button>
            <a href="{{ route('history') }}" class="secondary-button">Lihat Riwayat</a>
        </div>
    </form>

    <section class="space-y-4">
        <div class="panel p-5">
            <h2 class="text-lg font-bold">Validasi Integritas</h2>
            <div class="mt-4 space-y-3">
                <div class="rounded-lg bg-lime-50 p-4 text-sm text-lime-800">
                    {{ session('output_filename') ? 'Dekripsi berhasil: '.session('output_filename') : 'Contoh status: kunci cocok, file berhasil dibuka sebagai invoice-mei-2026.pdf.' }}
                    @if (session('download_url'))
                        <div class="mt-3">
                            <a class="secondary-button" href="{{ session('download_url') }}">Unduh file asli</a>
                        </div>
                    @endif
                </div>
                <div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-800">
                    Status gagal: kunci salah atau file rusak akan dicatat sebagai failed.
                </div>
            </div>
        </div>
        <div class="panel p-5">
            <h3 class="font-bold">Batasan Prototype</h3>
            <p class="mt-2 text-sm text-zinc-600">Upload, validasi kunci, audit log, dan download hasil sudah berjalan. Kunci rahasia tetap tidak disimpan.</p>
        </div>
    </section>
</div>
@endsection
