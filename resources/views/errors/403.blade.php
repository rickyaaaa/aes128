@extends('layouts.app')

@section('content')
<section class="panel mx-auto max-w-2xl p-8 text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-rose-100 text-xl font-black text-rose-700">403</div>
    <h2 class="mt-6 text-2xl font-bold">Akses Ditolak</h2>
    <p class="mt-3 text-zinc-600">Akun Anda tidak memiliki role yang diperlukan untuk membuka halaman ini.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
        @auth
            <a href="{{ route(auth()->user()->isOwner() ? 'owner.dashboard' : 'staff.dashboard') }}" class="primary-button">Kembali ke Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="primary-button">Masuk</a>
        @endauth
    </div>
</section>
@endsection
