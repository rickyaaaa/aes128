@php
    $showUser = $showUser ?? false;
    $canDelete = $canDelete ?? false;
    $canDecrypt = $canDecrypt ?? false;
    $canUpdatePassword = $canUpdatePassword ?? false;
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-zinc-200 text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-bold uppercase tracking-wider text-zinc-500">
        <tr>
            <th class="px-4 py-3">File</th>
            @if ($showUser)
                <th class="px-4 py-3">Pemilik</th>
            @endif
            <th class="px-4 py-3">Tipe</th>
            <th class="px-4 py-3">Ukuran</th>
            <th class="px-4 py-3">Waktu Upload</th>
            <th class="px-4 py-3">Aksi</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white">
        @forelse ($logs as $log)
            <tr>
                <td class="px-4 py-4">
                    <p class="font-semibold text-zinc-900">{{ $log->file_name }}</p>
                    <p class="mt-1 text-xs uppercase text-zinc-500">.{{ $log->file_type }}</p>
                </td>
                @if ($showUser)
                    <td class="px-4 py-4 text-zinc-700">{{ $log->user?->name ?? '-' }}</td>
                @endif
                <td class="px-4 py-4 text-zinc-700">{{ strtoupper($log->file_type) }}</td>
                <td class="px-4 py-4 text-zinc-700">{{ number_format($log->file_size / 1024, 1, ',', '.') }} KB</td>
                <td class="px-4 py-4 text-zinc-600">{{ $log->created_at?->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</td>
                <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('files.show', $log) }}" class="secondary-button min-h-9 px-3 py-1">Detail</a>
                        @if ($canDecrypt)
                            <button
                                type="button"
                                class="secondary-button min-h-9 px-3 py-1 decrypt-trigger"
                                data-file-name="{{ $log->file_name }}"
                                data-action="{{ route('files.decrypt', $log) }}"
                            >
                                Dekripsi
                            </button>
                        @endif
                        @if ($canUpdatePassword && $log->user_id === auth()->id())
                            <button
                                type="button"
                                class="secondary-button min-h-9 px-3 py-1 password-trigger"
                                data-file-name="{{ $log->file_name }}"
                                data-action="{{ route('files.password.update', $log) }}"
                            >
                                Edit Password
                            </button>
                        @endif
                        @if ($canDelete)
                            <form method="POST" action="{{ route('files.destroy', $log) }}" onsubmit="return confirm('Hapus file dan record ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="secondary-button min-h-9 px-3 py-1 text-rose-700">Hapus</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="px-4 py-8 text-center text-sm text-zinc-500" colspan="{{ $showUser ? 6 : 5 }}">Belum ada file terenkripsi.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($canDecrypt)
    <div id="decrypt-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-zinc-950/60 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-soft">
            <h3 class="text-lg font-bold">Dekripsi File</h3>
            <p id="decrypt-file-label" class="mt-1 text-sm text-zinc-600"></p>
            <form id="decrypt-form" method="POST" class="mt-4">
                @csrf
                <label class="block">
                    <span class="field-label">Kata Sandi</span>
                    <input class="field-input" type="password" name="secret_key" minlength="8" required>
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="secondary-button" id="decrypt-cancel">Batal</button>
                    <button type="submit" class="primary-button">Dekripsi & Download</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($canUpdatePassword)
    <div id="password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-zinc-950/60 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-soft">
            <h3 class="text-lg font-bold">Update Kata Sandi</h3>
            <p id="password-file-label" class="mt-1 text-sm text-zinc-600"></p>
            <form id="password-form" method="POST" class="mt-4">
                @csrf
                <label class="block">
                    <span class="field-label">Kata Sandi Lama</span>
                    <input class="field-input" type="password" name="old_secret_key" minlength="8" required>
                </label>
                <label class="block mt-4">
                    <span class="field-label">Kata Sandi Baru</span>
                    <input class="field-input" type="password" name="new_secret_key" minlength="8" required>
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="secondary-button" id="password-cancel">Batal</button>
                    <button type="submit" class="primary-button">Simpan Password Baru</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($canDecrypt || $canUpdatePassword)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const decryptModal = document.getElementById('decrypt-modal');
    const decryptForm = document.getElementById('decrypt-form');
    const decryptLabel = document.getElementById('decrypt-file-label');
    const decryptCancel = document.getElementById('decrypt-cancel');
    const passwordModal = document.getElementById('password-modal');
    const passwordForm = document.getElementById('password-form');
    const passwordLabel = document.getElementById('password-file-label');
    const passwordCancel = document.getElementById('password-cancel');

    document.querySelectorAll('.decrypt-trigger').forEach((button) => {
        button.addEventListener('click', function () {
            if (!decryptModal || !decryptForm || !decryptLabel) return;
            decryptForm.action = button.dataset.action;
            decryptLabel.textContent = 'File: ' + button.dataset.fileName;
            decryptModal.classList.remove('hidden');
            decryptModal.classList.add('flex');
        });
    });

    document.querySelectorAll('.password-trigger').forEach((button) => {
        button.addEventListener('click', function () {
            if (!passwordModal || !passwordForm || !passwordLabel) return;
            passwordForm.action = button.dataset.action;
            passwordLabel.textContent = 'File: ' + button.dataset.fileName;
            passwordModal.classList.remove('hidden');
            passwordModal.classList.add('flex');
        });
    });

    if (decryptCancel && decryptModal) {
        decryptCancel.addEventListener('click', function () {
            decryptModal.classList.add('hidden');
            decryptModal.classList.remove('flex');
        });
    }

    if (passwordCancel && passwordModal) {
        passwordCancel.addEventListener('click', function () {
            passwordModal.classList.add('hidden');
            passwordModal.classList.remove('flex');
        });
    }
});
</script>
@endif
