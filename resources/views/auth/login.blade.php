<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Yokprinting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="grid min-h-screen bg-zinc-950 text-white lg:grid-cols-[1.05fr_0.95fr]">
    <section class="flex items-center px-6 py-12 sm:px-10 lg:px-16">
        <div class="max-w-2xl">
            <img src="{{ asset('images/yokprinting-logo.svg') }}" alt="Yokprinting" class="h-16 w-auto rounded-lg bg-white px-3 py-2">
            <h1 class="mt-8 text-4xl font-bold tracking-normal sm:text-5xl">Keamanan File Yokprinting</h1>
            <p class="mt-5 text-lg leading-8 text-zinc-300">Sistem internal Yokprinting untuk enkripsi, dekripsi, pengelolaan file, dan pemisahan akses Owner serta Staff.</p>
            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                    <p class="text-2xl font-bold text-emerald-300">JPG</p>
                    <p class="mt-1 text-sm text-zinc-400">Desain visual</p>
                </div>
                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                    <p class="text-2xl font-bold text-sky-300">PNG</p>
                    <p class="mt-1 text-sm text-zinc-400">Aset klien</p>
                </div>
                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                    <p class="text-2xl font-bold text-amber-300">PDF</p>
                    <p class="mt-1 text-sm text-zinc-400">Invoice</p>
                </div>
            </div>
        </div>
    </section>

    <section class="flex items-center bg-zinc-50 px-6 py-12 text-zinc-950 sm:px-10 lg:px-16">
        <form method="POST" action="{{ route('login.store') }}" class="panel w-full max-w-md p-6">
            @csrf
            <div>
                <p class="text-sm font-semibold text-emerald-700">Yokprinting Internal System</p>
                <h2 class="mt-2 text-2xl font-bold">Masuk ke sistem</h2>
                <p class="mt-2 text-sm text-zinc-600">Sistem membaca role dari akun dan mengarahkan ke dashboard yang sesuai.</p>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <label class="block">
                    <span class="field-label">Username</span>
                    <input class="field-input" type="text" name="username" value="{{ old('username', 'owner') }}" autocomplete="username" required autofocus>
                </label>
                <label class="block">
                    <span class="field-label">Password</span>
                    <input class="field-input" type="password" name="password" autocomplete="current-password" required>
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold text-zinc-700">
                    <input class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500" type="checkbox" name="remember" value="1">
                    Ingat sesi
                </label>
            </div>

            <button class="primary-button mt-6 w-full" type="submit">Masuk</button>
            <div class="mt-4 rounded-lg bg-zinc-100 p-3 text-sm text-zinc-600">
                Akun awal seeder: owner / password. Registrasi publik dinonaktifkan.
            </div>
        </form>
    </section>
</main>
</body>
</html>
