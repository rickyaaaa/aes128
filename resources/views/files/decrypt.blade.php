@extends('layouts.app')

@section('content')
@php
    $selectedFileLog = $fileLog ?? null;
    $secretKeyError = $selectedFileLog ? 'decrypt_secret_key' : 'secret_key';
@endphp

<div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <form
        method="POST"
        action="{{ $selectedFileLog ? route('files.decrypt', $selectedFileLog) : route('files.decrypt.store') }}"
        @if (! $selectedFileLog) enctype="multipart/form-data" @endif
        class="panel p-5"
    >
        @csrf
        <h2 class="text-lg font-bold">Dekripsi File</h2>
        <p class="mt-1 text-sm text-zinc-600">
            @if ($selectedFileLog)
                Masukkan Kata Sandi untuk file dari riwayat sistem ini, lalu file asli akan otomatis diunduh.
            @else
                Unggah file .enc mana pun (tidak harus dari riwayat sistem ini) beserta Kata Sandi-nya untuk memulihkan file aslinya.
            @endif
        </p>

        <div class="mt-6 space-y-5">
            @if ($selectedFileLog)
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                    <span class="field-label">File terenkripsi</span>
                    <p class="mt-1 font-semibold text-zinc-900">{{ $selectedFileLog->file_name }}</p>
                    <p class="mt-1 text-xs uppercase text-zinc-500">.{{ $selectedFileLog->file_type }}</p>
                </div>
            @else
                <label class="block">
                    <span class="field-label">File terenkripsi (.enc)</span>
                    <input class="field-input" type="file" name="source_file" accept=".enc" required>
                    @error('source_file')
                        <span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>
                    @enderror
                </label>
            @endif

            <label class="block">
                <span class="field-label">Kata Sandi</span>
                <input class="field-input" type="password" name="secret_key" minlength="8" required>
                @error($secretKeyError)
                    <span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="primary-button" type="submit">Dekripsi & Download</button>
            @if ($selectedFileLog)
                <a href="{{ route('files.decrypt.create') }}" class="secondary-button">Dekripsi File Lain</a>
            @endif
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
                @if ($selectedFileLog)
                    <li>File diambil langsung dari riwayat sistem, jadi tidak perlu upload ulang file .enc.</li>
                @else
                    <li>Fitur ini berdiri sendiri dari Riwayat File - file .enc dari perangkat atau proses lain tetap bisa didekripsi selama Kata Sandinya benar.</li>
                @endif
            </ul>
        </div>
    </section>
</div>
@endsection
