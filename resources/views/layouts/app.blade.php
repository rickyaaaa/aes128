<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Yokprinting File Security' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $role = $role ?? auth()->user()?->role ?? 'staff';
    $isOwner = $role === 'owner';
    $navItems = [
        ['label' => 'Dashboard', 'route' => $isOwner ? 'owner.dashboard' : 'staff.dashboard', 'active' => request()->routeIs($isOwner ? 'owner.dashboard' : 'staff.dashboard')],
        ['label' => 'Enkripsi', 'route' => 'files.encrypt', 'active' => request()->routeIs('files.encrypt')],
        ['label' => $isOwner ? 'Riwayat File' : 'Riwayat Saya', 'route' => 'history', 'active' => request()->routeIs('history')],
        ['label' => 'Tentang Aplikasi', 'route' => 'about', 'active' => request()->routeIs('about')],
    ];
    $ownerItems = [
        ['label' => 'Manajemen User', 'route' => 'owner.users', 'active' => request()->routeIs('owner.users')],
    ];
@endphp

<div class="min-h-screen lg:flex">
    <aside class="hidden w-72 shrink-0 border-r border-zinc-200 bg-zinc-950 text-white lg:block">
        <div class="flex h-full flex-col p-6">
            <a href="{{ route($isOwner ? 'owner.dashboard' : 'staff.dashboard') }}" class="space-y-3">
                <img src="{{ asset('images/yokprinting-logo.svg') }}" alt="Yokprinting" class="h-12 w-auto rounded-lg bg-white px-2 py-1">
                <div>
                    <p class="text-lg font-bold">Yokprinting</p>
                    <p class="text-sm text-zinc-400">Keamanan file perusahaan</p>
                </div>
            </a>

            <nav class="mt-8 space-y-1">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="block rounded-lg px-3 py-2 text-sm font-semibold transition {{ $item['active'] ? 'bg-emerald-500 text-zinc-950' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @if ($isOwner)
                    <div class="pt-5">
                        <p class="px-3 text-xs font-bold uppercase tracking-wider text-zinc-500">Owner</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($ownerItems as $item)
                                <a href="{{ route($item['route']) }}" class="block rounded-lg px-3 py-2 text-sm font-semibold transition {{ $item['active'] ? 'bg-amber-400 text-zinc-950' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>

            <div class="mt-auto rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">Sesi aktif</p>
                <p class="mt-2 text-sm text-zinc-300">Role: <span class="font-semibold text-white">{{ ucfirst($role) }}</span></p>
                <p class="mt-1 truncate text-sm text-zinc-400">{{ auth()->user()?->username }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="text-sm font-semibold text-emerald-300 hover:text-emerald-200" type="submit">Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">Sistem Keamanan File</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-normal text-zinc-950">{{ $pageTitle ?? 'Dashboard' }}</h1>
                        <p class="mt-1 max-w-3xl text-sm text-zinc-600">{{ $pageDescription ?? 'Prototype frontend Laravel Blade + Tailwind CSS.' }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-semibold text-zinc-700">
                        {{ $isOwner ? 'Owner' : 'Staff' }}
                    </div>
                </div>

                <nav class="flex flex-wrap gap-2 pb-1 lg:hidden">
                    @foreach (array_merge($navItems, $isOwner ? $ownerItems : []) as $item)
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ $item['active'] ? 'bg-zinc-950 text-white' : 'bg-zinc-100 text-zinc-700' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Periksa kembali input berikut:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
