@extends('layouts.app')

@section('content')
<section class="panel p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold">{{ $role === 'owner' ? 'Riwayat File Sistem' : 'Riwayat File Personal' }}</h2>
            <p class="mt-1 text-sm text-zinc-600">{{ $role === 'owner' ? 'Owner dapat melihat data global.' : 'Staff hanya melihat log miliknya sendiri.' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('files.encrypt') }}" class="primary-button">Enkripsi Baru</a>
            <a href="{{ route('files.decrypt') }}" class="secondary-button">Dekripsi Baru</a>
        </div>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-zinc-100 p-4">
            <p class="text-sm font-semibold text-zinc-500">Total tampil</p>
            <p class="mt-2 text-2xl font-bold">{{ count($visibleLogs) }}</p>
        </div>
        <div class="rounded-lg bg-emerald-50 p-4">
            <p class="text-sm font-semibold text-emerald-700">Berhasil</p>
            <p class="mt-2 text-2xl font-bold">{{ count(array_filter($visibleLogs, fn ($log) => $log['status'] === 'success')) }}</p>
        </div>
        <div class="rounded-lg bg-rose-50 p-4">
            <p class="text-sm font-semibold text-rose-700">Gagal</p>
            <p class="mt-2 text-2xl font-bold">{{ count(array_filter($visibleLogs, fn ($log) => $log['status'] === 'failed')) }}</p>
        </div>
    </div>

    <div class="mt-5">
        @include('partials.log-table', ['logs' => $visibleLogs])
    </div>
</section>
@endsection
