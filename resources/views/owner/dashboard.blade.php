@extends('layouts.app')

@section('content')
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($stats as $stat)
        <div class="panel p-5">
            <p class="text-sm font-semibold text-zinc-500">{{ $stat['label'] }}</p>
            <p class="mt-3 text-3xl font-bold {{ $stat['tone'] }}">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <section class="panel p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Aktivitas Mingguan</h2>
                <p class="mt-1 text-sm text-zinc-600">Jumlah proses enkripsi/dekripsi per hari.</p>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Database</span>
        </div>
        <div class="mt-6 flex h-60 items-end gap-3">
            @foreach ($activity as $day)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="w-full rounded-t-lg bg-emerald-500" style="height: {{ max(18, $day['count'] * 10) }}px"></div>
                    <span class="text-xs font-semibold text-zinc-500">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="panel p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Daftar File Terenkripsi</h2>
                <p class="mt-1 text-sm text-zinc-600">Filter extension dan rentang tanggal berjalan dinamis saat dipilih.</p>
            </div>
            <a href="{{ route('history') }}" class="secondary-button">Lihat Semua</a>
        </div>
        <form method="GET" class="mt-4 grid gap-3 md:grid-cols-3" id="owner-filter-form">
            <select class="field-input mt-0" name="file_type" id="owner-file-type-filter">
                <option value="">Semua Ekstensi</option>
                @foreach ($extensions as $extension)
                    <option value="{{ $extension }}" @selected(($filters['file_type'] ?? '') === $extension)>{{ strtoupper($extension) }}</option>
                @endforeach
            </select>
            <input class="field-input mt-0" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" id="owner-date-from-filter">
            <input class="field-input mt-0" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" id="owner-date-to-filter">
        </form>
        <div class="mt-5">
            @include('partials.log-table', [
                'logs' => $logs,
                'showUser' => true,
                'canDelete' => true,
                'canDecrypt' => true,
                'canUpdatePassword' => false,
            ])
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('owner-filter-form');
    ['owner-file-type-filter', 'owner-date-from-filter', 'owner-date-to-filter'].forEach((id) => {
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
