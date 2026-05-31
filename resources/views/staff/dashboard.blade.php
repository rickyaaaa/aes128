@extends('layouts.app')

@section('content')
<div class="grid gap-4 md:grid-cols-3">
    <div class="panel p-5">
        <p class="text-sm font-semibold text-zinc-500">File Saya Terenkripsi</p>
        <p class="mt-3 text-3xl font-bold text-emerald-700">{{ $stats['total'] }}</p>
    </div>
    <div class="panel p-5">
        <p class="text-sm font-semibold text-zinc-500">Total Ukuran File</p>
        <p class="mt-3 text-3xl font-bold text-sky-700">{{ $stats['size'] }}</p>
    </div>
    <div class="panel p-5">
        <p class="text-sm font-semibold text-zinc-500">Upload Hari Ini</p>
        <p class="mt-3 text-3xl font-bold text-amber-700">{{ $stats['today'] }}</p>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
    <section class="panel p-5">
        <h2 class="text-lg font-bold">Aksi Cepat</h2>
        <p class="mt-1 text-sm text-zinc-600">Dekripsi dan update password dilakukan dari daftar file milik sendiri.</p>
        <div class="mt-5 grid gap-3">
            <a href="{{ route('files.encrypt') }}" class="primary-button justify-between">Buka Ruang Gembok <span>Enkripsi</span></a>
            <a href="{{ route('history') }}" class="secondary-button justify-between">Kelola File Saya <span>Riwayat</span></a>
            <a href="{{ route('unauthorized') }}" class="secondary-button justify-between">Simulasi Akses Owner <span>403</span></a>
        </div>
    </section>

    <section class="panel p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Riwayat Terakhir Saya</h2>
                <p class="mt-1 text-sm text-zinc-600">Staff dapat decrypt, download, dan update password file sendiri.</p>
            </div>
            <a href="{{ route('history') }}" class="secondary-button">Lihat Riwayat</a>
        </div>
        <div class="mt-5">
            @include('partials.log-table', [
                'logs' => $staffLogs,
                'showUser' => false,
                'canDelete' => false,
                'canDecrypt' => true,
                'canUpdatePassword' => true,
            ])
        </div>
    </section>
</div>
@endsection
