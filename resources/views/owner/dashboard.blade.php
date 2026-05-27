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
                <h2 class="text-lg font-bold">Audit Terbaru</h2>
                <p class="mt-1 text-sm text-zinc-600">Owner melihat seluruh log sistem.</p>
            </div>
            <a href="{{ route('owner.audit') }}" class="secondary-button">Lihat Semua</a>
        </div>
        <div class="mt-5">
            @include('partials.log-table', ['logs' => array_slice($logs, 0, 3)])
        </div>
    </section>
</div>
@endsection
