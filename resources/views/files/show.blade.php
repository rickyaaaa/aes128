@extends('layouts.app')

@section('content')
<section class="panel p-5">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200 pb-4">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Detail untuk {{ $fileLog->file_name }}</p>
            <h2 class="mt-1 text-xl font-bold text-zinc-950">Informasi File Terenkripsi</h2>
        </div>
        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase text-emerald-800">
            {{ $fileLog->file_type }}
        </span>
    </div>

    <dl class="mt-5 grid gap-4 text-sm md:grid-cols-2">
        <div>
            <dt class="font-semibold text-zinc-500">Nama File</dt>
            <dd class="mt-1 font-medium text-zinc-900">{{ $fileLog->file_name }}</dd>
        </div>
        <div>
            <dt class="font-semibold text-zinc-500">Pemilik</dt>
            <dd class="mt-1 font-medium text-zinc-900">{{ $fileLog->user?->name ?? 'User dihapus' }}</dd>
        </div>
        <div>
            <dt class="font-semibold text-zinc-500">Ukuran</dt>
            <dd class="mt-1 font-medium text-zinc-900">{{ number_format($fileLog->file_size / 1024, 1, ',', '.') }} KB</dd>
        </div>
        <div>
            <dt class="font-semibold text-zinc-500">Ekstensi</dt>
            <dd class="mt-1 font-medium uppercase text-zinc-900">{{ $fileLog->file_type }}</dd>
        </div>
        <div>
            <dt class="font-semibold text-zinc-500">Diunggah Pada</dt>
            <dd class="mt-1 font-medium text-zinc-900">{{ $fileLog->created_at?->format('d M Y, H:i') }}</dd>
        </div>
        <div>
            <dt class="font-semibold text-zinc-500">Alamat IP</dt>
            <dd class="mt-1 font-medium text-zinc-900">{{ $fileLog->ip_address ?? '-' }}</dd>
        </div>
    </dl>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route($role === 'owner' ? 'owner.dashboard' : 'staff.dashboard') }}" class="secondary-button">Kembali</a>
    </div>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="panel p-5">
        <h2 class="text-lg font-bold">Dekripsi File</h2>
        <p class="mt-1 text-sm text-zinc-600">Masukkan kata sandi file untuk mengunduh isi aslinya.</p>
        <form method="POST" action="{{ route('files.decrypt', $fileLog) }}" class="mt-4">
            @csrf
            <label class="block">
                <span class="field-label">Kata Sandi</span>
                <input class="field-input" type="password" name="secret_key" minlength="8" required>
            </label>
            <button type="submit" class="primary-button mt-4">Dekripsi & Download</button>
        </form>
    </section>

    @if ($fileLog->user_id === auth()->id())
        <section class="panel p-5">
            <h2 class="text-lg font-bold">Edit Kata Sandi</h2>
            <p class="mt-1 text-sm text-zinc-600">Ganti kata sandi hanya untuk file milik akun Anda.</p>
            <form method="POST" action="{{ route('files.password.update', $fileLog) }}" class="mt-4">
                @csrf
                <label class="block">
                    <span class="field-label">Kata Sandi Lama</span>
                    <input class="field-input" type="password" name="old_secret_key" minlength="8" required>
                </label>
                <label class="mt-4 block">
                    <span class="field-label">Kata Sandi Baru</span>
                    <input class="field-input" type="password" name="new_secret_key" minlength="8" required>
                </label>
                <button type="submit" class="primary-button mt-4">Simpan Password Baru</button>
            </form>
        </section>
    @endif
</div>
@endsection
