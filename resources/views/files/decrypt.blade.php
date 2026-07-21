@extends('layouts.app')

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <form method="POST" action="{{ route('files.decrypt.store') }}" enctype="multipart/form-data" class="panel p-5">
        @csrf
        <h2 class="text-lg font-bold">Dekripsi File</h2>
        <p class="mt-1 text-sm text-zinc-600">Unggah file .enc mana pun (tidak harus dari riwayat sistem ini) beserta Kata Sandi-nya untuk memulihkan file aslinya.</p>

        <div class="mt-6 space-y-5">
            <label class="block">
                <span class="field-label">File terenkripsi (.enc)</span>
                <input class="field-input" type="file" name="source_file" accept=".enc">
                @error('source_file')
                    <span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>
                @enderror
            </label>
            <label class="block">
                <span class="field-label">Kata Sandi</span>
                <input class="field-input" type="password" name="secret_key" minlength="8" required>
                @error('secret_key')
                    <span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="primary-button" type="submit">Dekripsi & Download</button>
            <a href="{{ route('files.encrypt') }}" class="secondary-button">Ke Menu Enkripsi</a>
        </div>
    </form>

    <section class="space-y-4">
        @if (session('decrypt_time'))
            <div class="panel p-5">
                <h3 class="font-bold">Waktu Proses</h3>
                <p class="mt-2 text-sm text-zinc-600">Waktu Dekripsi : {{ session('decrypt_time') }} detik</p>
            </div>
        @endif
        <div class="panel p-5">
            <h3 class="font-bold">Catatan</h3>
            <ul class="mt-3 space-y-2 text-sm text-zinc-600">
                <li>File hasil didekripsi otomatis dikembalikan ke nama aslinya.</li>
                <li>Kata sandi tidak disimpan atau dikirim ke mana pun selain untuk proses dekripsi ini.</li>
                <li>Fitur ini berdiri sendiri dari Riwayat File — file .enc dari perangkat atau proses lain tetap bisa didekripsi selama Kata Sandinya benar.</li>
            </ul>
        </div>
    </section>
</div>
@endsection
