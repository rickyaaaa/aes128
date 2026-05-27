@extends('layouts.app')

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <form method="POST" action="{{ route('files.encrypt.store') }}" enctype="multipart/form-data" class="panel p-5">
        @csrf
        <h2 class="text-lg font-bold">Ruang Gembok</h2>
        <p class="mt-1 text-sm text-zinc-600">Input file desain dan invoice sebelum dikunci menjadi format .enc.</p>

        <div class="mt-6 space-y-5">
            <label class="block">
                <span class="field-label">File sumber</span>
                <input class="field-input" type="file" name="source_file" accept=".jpg,.png,.pdf">
                <span class="mt-2 block text-xs text-zinc-500">Format diterima: JPG, PNG, PDF. Maksimal 20 MB.</span>
            </label>
            <label class="block">
                <span class="field-label">Kunci Rahasia</span>
                <input class="field-input" type="password" name="secret_key" placeholder="Masukkan kunci setiap proses">
                <span class="mt-2 block text-xs text-zinc-500">Kunci tidak akan disimpan di database sesuai rancangan PRD.</span>
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="primary-button" type="submit">Amankan File</button>
            <a href="{{ route('history') }}" class="secondary-button">Lihat Riwayat</a>
        </div>
    </form>

    <section class="space-y-4">
        <div class="panel p-5">
            <h2 class="text-lg font-bold">Status Hasil</h2>
            <div class="mt-4 rounded-lg border border-dashed border-emerald-300 bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-900">{{ session('output_filename', 'Belum ada file diproses') }}</p>
                <p class="mt-1 text-sm text-emerald-800">File terenkripsi disimpan di Laravel storage dan dapat diunduh setelah proses berhasil.</p>
                @if (session('download_url'))
                    <a class="secondary-button mt-4" href="{{ session('download_url') }}">Unduh .enc</a>
                @endif
            </div>
        </div>
        <div class="panel p-5">
            <h3 class="font-bold">Catatan Keamanan</h3>
            <ul class="mt-3 space-y-2 text-sm text-zinc-600">
                <li>Secret key diminta setiap proses.</li>
                <li>Backend menjalankan OpenSSL AES-128-CBC.</li>
                <li>Audit log menyimpan metadata, bukan kunci rahasia.</li>
            </ul>
        </div>
    </section>
</div>
@endsection
