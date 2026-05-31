@extends('layouts.app')

@section('content')
<div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <section class="panel p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Daftar Staff</h2>
                <p class="mt-1 text-sm text-zinc-600">Owner dapat membuat, memperbarui, menonaktifkan, dan menghapus akun Staff.</p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Owner only</span>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-bold uppercase tracking-wider text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Staff</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Edit Cepat</th>
                    <th class="px-4 py-3">Hapus</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-4 align-top">
                            <p class="font-semibold text-zinc-900">{{ $user['name'] }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $user['username'] }}</p>
                            <p class="mt-1 text-xs capitalize text-zinc-500">{{ $user['role'] }} - {{ $user['last_seen'] }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $user['status'] === 'Aktif' ? 'bg-lime-100 text-lime-800' : 'bg-zinc-200 text-zinc-700' }}">{{ $user['status'] }}</span>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <form method="POST" action="{{ route('owner.users.update', $user['id']) }}" class="grid min-w-72 gap-2">
                                @csrf
                                @method('PATCH')
                                <input class="field-input mt-0" type="text" name="name" value="{{ $user['name'] }}" aria-label="Nama {{ $user['name'] }}">
                                <input class="field-input mt-0" type="text" name="username" value="{{ $user['username'] }}" aria-label="Username {{ $user['name'] }}">
                                <input class="field-input mt-0" type="password" name="password" placeholder="Password baru opsional" aria-label="Password baru {{ $user['name'] }}">
                                <select class="field-input mt-0" name="is_active" aria-label="Status {{ $user['name'] }}">
                                    <option value="1" @selected($user['status'] === 'Aktif')>Aktif</option>
                                    <option value="0" @selected($user['status'] === 'Nonaktif')>Nonaktif</option>
                                </select>
                                <button class="secondary-button min-h-9 px-3 py-1" type="submit">Simpan Edit</button>
                            </form>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <form method="POST" action="{{ route('owner.users.destroy', $user['id']) }}">
                                @csrf
                                @method('DELETE')
                                <button class="secondary-button min-h-9 px-3 py-1 text-rose-700" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-zinc-500" colspan="4">Belum ada akun Staff.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <form method="POST" action="{{ route('owner.users.store') }}" class="panel p-5">
        @csrf
        <h2 class="text-lg font-bold">Tambah Staff</h2>
        <p class="mt-1 text-sm text-zinc-600">Registrasi publik dinonaktifkan; Staff dibuat dari panel Owner.</p>

        <div class="mt-6 space-y-4">
            <label class="block">
                <span class="field-label">Nama</span>
                <input class="field-input" type="text" name="name" value="{{ old('name') }}" placeholder="Nama staff" required>
            </label>
            <label class="block">
                <span class="field-label">Username</span>
                <input class="field-input" type="text" name="username" value="{{ old('username') }}" placeholder="username staff" required>
            </label>
            <label class="block">
                <span class="field-label">Password awal</span>
                <input class="field-input" type="password" name="password" placeholder="Minimal 8 karakter" required>
            </label>
            <label class="block">
                <span class="field-label">Status</span>
                <select class="field-input" name="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>
        </div>

        <button class="primary-button mt-6 w-full" type="submit">Simpan Staff</button>
    </form>
</div>
@endsection
