@extends('layouts.app')

@section('content')
<section class="panel mx-auto max-w-2xl p-8 text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-rose-100 text-xl font-black text-rose-700">403</div>
    <h2 class="mt-6 text-2xl font-bold">Akses Ditolak</h2>
    <p class="mt-3 text-zinc-600">Middleware <span class="font-semibold">role:owner</span> menolak Staff yang mencoba membuka dashboard Owner, manajemen user, atau audit global.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <a href="{{ route('staff.dashboard') }}" class="primary-button">Kembali ke Dashboard Staff</a>
        <a href="{{ route('login') }}" class="secondary-button">Ganti Role Demo</a>
    </div>
</section>
@endsection
