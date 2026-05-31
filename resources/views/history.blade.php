@extends('layouts.app')

@section('content')
<section class="panel p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold">{{ $role === 'owner' ? 'Riwayat File Sistem' : 'Riwayat File Personal' }}</h2>
            <p class="mt-1 text-sm text-zinc-600">{{ $role === 'owner' ? 'Owner dapat melihat semua file terenkripsi dan menghapus record.' : 'Staff hanya melihat file miliknya sendiri serta dapat dekripsi dan update password.' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('files.encrypt') }}" class="primary-button">Enkripsi Baru</a>
        </div>
    </div>

    <form method="GET" class="mt-4 grid gap-3 md:grid-cols-3" id="history-filter-form">
        <select class="field-input mt-0" name="file_type" id="history-file-type-filter">
            <option value="">Semua Ekstensi</option>
            @foreach (($extensions ?? []) as $extension)
                <option value="{{ $extension }}" @selected(($filters['file_type'] ?? '') === $extension)>{{ strtoupper($extension) }}</option>
            @endforeach
        </select>
        <input class="field-input mt-0" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" id="history-date-from-filter">
        <input class="field-input mt-0" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" id="history-date-to-filter">
    </form>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-zinc-100 p-4">
            <p class="text-sm font-semibold text-zinc-500">Total tampil</p>
            <p class="mt-2 text-2xl font-bold">{{ count($visibleLogs) }}</p>
        </div>
        <div class="rounded-lg bg-emerald-50 p-4">
            <p class="text-sm font-semibold text-emerald-700">Ekstensi unik</p>
            <p class="mt-2 text-2xl font-bold">{{ count(array_unique(array_map(fn ($log) => $log->file_type, $visibleLogs))) }}</p>
        </div>
        <div class="rounded-lg bg-rose-50 p-4">
            <p class="text-sm font-semibold text-rose-700">Total Ukuran</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format(array_sum(array_map(fn ($log) => $log->file_size, $visibleLogs)) / 1024, 1, ',', '.') }} KB</p>
        </div>
    </div>

    <div class="mt-5">
        @include('partials.log-table', [
            'logs' => $visibleLogs,
            'showUser' => $role === 'owner',
            'canDelete' => $role === 'owner',
            'canDecrypt' => true,
            'canUpdatePassword' => $role !== 'owner',
        ])
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('history-filter-form');
    ['history-file-type-filter', 'history-date-from-filter', 'history-date-to-filter'].forEach((id) => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function () {
                filterForm.submit();
            });
        }
    });
});
</script>
@endsection
