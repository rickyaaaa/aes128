<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-zinc-200 text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-bold uppercase tracking-wider text-zinc-500">
        <tr>
            <th class="px-4 py-3">File</th>
            <th class="px-4 py-3">User</th>
            <th class="px-4 py-3">Proses</th>
            <th class="px-4 py-3">Ukuran</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Waktu</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white">
        @forelse ($logs as $log)
            <tr>
                <td class="px-4 py-4">
                    <p class="font-semibold text-zinc-900">{{ $log['filename'] }}</p>
                    <p class="mt-1 text-xs uppercase text-zinc-500">{{ $log['type'] }} - {{ $log['ip'] }}</p>
                    @if (! empty($log['download_url']))
                        <a href="{{ $log['download_url'] }}" class="mt-2 inline-flex text-xs font-bold text-emerald-700 hover:text-emerald-900">Unduh hasil</a>
                    @endif
                </td>
                <td class="px-4 py-4 text-zinc-700">{{ $log['user'] }}</td>
                <td class="px-4 py-4">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $log['process'] === 'encryption' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                        {{ $log['process'] === 'encryption' ? 'Enkripsi' : 'Dekripsi' }}
                    </span>
                </td>
                <td class="px-4 py-4 text-zinc-700">{{ $log['size'] }}</td>
                <td class="px-4 py-4">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $log['status'] === 'success' ? 'bg-lime-100 text-lime-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $log['status'] === 'success' ? 'Berhasil' : 'Gagal' }}
                    </span>
                </td>
                <td class="px-4 py-4 text-zinc-600">{{ $log['created_at'] }}</td>
            </tr>
        @empty
            <tr>
                <td class="px-4 py-8 text-center text-sm text-zinc-500" colspan="6">Belum ada riwayat file.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
