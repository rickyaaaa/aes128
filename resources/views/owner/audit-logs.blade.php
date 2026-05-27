@extends('layouts.app')

@section('content')
<section class="panel p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold">Riwayat Audit Seluruh Sistem</h2>
            <p class="mt-1 text-sm text-zinc-600">Filter di bawah bersifat visual untuk prototype frontend.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button class="secondary-button" type="button">Semua</button>
            <button class="secondary-button" type="button">Enkripsi</button>
            <button class="secondary-button" type="button">Dekripsi</button>
            <button class="secondary-button" type="button">Gagal</button>
        </div>
    </div>
    <div class="mt-5">
        @include('partials.log-table', ['logs' => $logs])
    </div>
</section>
@endsection
