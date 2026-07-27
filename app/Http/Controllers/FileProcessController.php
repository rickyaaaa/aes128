<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidSecretKeyException;
use App\Http\Requests\DecryptFileRequest;
use App\Http\Requests\EncryptFileRequest;
use App\Models\CryptoProcessLog;
use App\Models\FileLog;
use App\Services\FileCryptoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileProcessController extends Controller
{
    public function createEncryption(Request $request): View
    {
        return view('files.encrypt', [
            'role' => $request->user()->role,
            'pageTitle' => 'Enkripsi File',
            'pageDescription' => 'Unggah file JPG, PNG, atau PDF lalu masukkan Kata Sandi dinamis untuk enkripsi AES-128-CBC.',
        ]);
    }

    public function storeEncryption(EncryptFileRequest $request, FileCryptoService $cryptoService): RedirectResponse
    {
        $startTime = microtime(true);

        $result = $cryptoService->encryptUploadedFile(
            $request->file('source_file'),
            $request->string('secret_key')->toString(),
        );

        $executionTime = round(microtime(true) - $startTime, 4);

        $fileLog = FileLog::create([
            'user_id' => $request->user()->id,
            'file_name' => $result['file_name'],
            'stored_path' => $result['stored_path'],
            'file_size' => $result['file_size'],
            'file_type' => $result['file_type'],
            'ip_address' => $request->ip(),
        ]);

        $this->recordCryptoProcess(
            $request,
            CryptoProcessLog::OPERATION_ENCRYPT,
            $executionTime,
            $fileLog,
            $fileLog->file_name,
            $fileLog->file_size,
            $fileLog->file_type,
        );

        return redirect()->route('files.show', $fileLog)
            ->with('status', 'File berhasil dienkripsi dan disimpan.')
            ->with('output_filename', $fileLog->file_name.'.enc')
            ->with('encrypt_time', $executionTime);
    }

    public function createDecryption(Request $request): View
    {
        return view('files.decrypt', [
            'role' => $request->user()->role,
            'pageTitle' => 'Dekripsi File',
            'pageDescription' => 'Unggah file .enc apa pun beserta Kata Sandi-nya untuk memulihkan file aslinya.',
        ]);
    }

    public function storeDecryption(DecryptFileRequest $request, FileCryptoService $cryptoService): StreamedResponse|RedirectResponse
    {
        $startTime = microtime(true);

        try {
            $decrypted = $cryptoService->decryptUploadedFile(
                $request->file('source_file'),
                $request->string('secret_key')->toString(),
            );
        } catch (InvalidSecretKeyException) {
            return back()->withErrors(['secret_key' => 'File .enc tidak valid atau kata sandi salah']);
        }

        $executionTime = round(microtime(true) - $startTime, 4);
        session()->flash('decrypt_time', $executionTime);

        $this->recordCryptoProcess(
            $request,
            CryptoProcessLog::OPERATION_DECRYPT,
            $executionTime,
            null,
            $decrypted['original_file_name'],
            (int) strlen($decrypted['plain_bytes']),
            pathinfo($decrypted['original_file_name'], PATHINFO_EXTENSION) ?: null,
        );

        return response()->streamDownload(function () use ($decrypted): void {
            echo $decrypted['plain_bytes'];
        }, $decrypted['original_file_name']);
    }

    public function show(Request $request, FileLog $fileLog): View
    {
        $this->authorizeOwnerOrFileOwner($request, $fileLog);

        return view('files.show', [
            'role' => $request->user()->role,
            'fileLog' => $fileLog->load('user'),
            'latestEncryptTime' => $fileLog->cryptoProcessLogs()
                ->encryptions()
                ->latest()
                ->value('execution_time_seconds'),
            'latestDecryptTime' => $fileLog->cryptoProcessLogs()
                ->decryptions()
                ->latest()
                ->value('execution_time_seconds'),
            'pageTitle' => 'Detail File',
            'pageDescription' => 'Lihat metadata file terenkripsi dan pilih aksi yang diperlukan.',
        ]);
    }

    public function decrypt(Request $request, FileLog $fileLog, FileCryptoService $cryptoService): StreamedResponse|RedirectResponse
    {
        $this->authorizeOwnerOrFileOwner($request, $fileLog);

        $validated = $request->validate([
            'secret_key' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $startTime = microtime(true);

        try {
            $decrypted = $cryptoService->decryptFromLog($fileLog, $validated['secret_key']);
        } catch (InvalidSecretKeyException) {
            return back()->withErrors(['decrypt_secret_key' => 'Invalid password']);
        }

        $executionTime = round(microtime(true) - $startTime, 4);
        session()->flash('decrypt_time', $executionTime);

        $this->recordCryptoProcess(
            $request,
            CryptoProcessLog::OPERATION_DECRYPT,
            $executionTime,
            $fileLog,
            $decrypted['original_file_name'],
            (int) strlen($decrypted['plain_bytes']),
            pathinfo($decrypted['original_file_name'], PATHINFO_EXTENSION) ?: $fileLog->file_type,
        );

        return response()->streamDownload(function () use ($decrypted): void {
            echo $decrypted['plain_bytes'];
        }, $decrypted['original_file_name']);
    }

    public function updatePassword(Request $request, FileLog $fileLog, FileCryptoService $cryptoService): RedirectResponse
    {
        $this->authorizeOwnerFileOnly($request, $fileLog);

        $validated = $request->validate([
            'old_secret_key' => ['required', 'string', 'min:8', 'max:255'],
            'new_secret_key' => ['required', 'string', 'min:8', 'max:255', 'different:old_secret_key'],
        ]);

        try {
            // Trial decryption (full bytes in-memory) to verify old passphrase before re-encrypt.
            $cryptoService->rotatePassphrase($fileLog, $validated['old_secret_key'], $validated['new_secret_key']);
        } catch (InvalidSecretKeyException) {
            return back()->withErrors(['old_secret_key' => 'Invalid password']);
        }

        return back()->with('status', 'Kata Sandi file berhasil diperbarui.');
    }

    public function download(Request $request, FileLog $fileLog): StreamedResponse
    {
        $this->authorizeOwnerOrFileOwner($request, $fileLog);

        abort_unless(Storage::disk('local')->exists($fileLog->stored_path), 404);

        return Storage::disk('local')->download($fileLog->stored_path, $fileLog->file_name.'.enc');
    }

    public function destroy(Request $request, FileLog $fileLog): RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        if (Storage::disk('local')->exists($fileLog->stored_path)) {
            Storage::disk('local')->delete($fileLog->stored_path);
        }

        $fileLog->delete();

        return back()->with('status', 'Record file dan berkas terenkripsi berhasil dihapus.');
    }

    public function history(Request $request): View
    {
        $validated = $request->validate([
            'file_type' => ['nullable', 'string', 'max:32'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = FileLog::with(['user', 'latestEncryptionProcess', 'latestDecryptionProcess'])->latest();

        if (! $request->user()->isOwner()) {
            $query->where('user_id', $request->user()->id);
        }

        if (! empty($validated['file_type'])) {
            $query->where('file_type', $validated['file_type']);
        }

        if (! empty($validated['date_from'])) {
            $query->createdOnOrAfterLocalDate($validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->createdBeforeOrOnLocalDate($validated['date_to']);
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('history', [
            'role' => $request->user()->role,
            'visibleLogs' => $rows->items(),
            'extensions' => FileLog::query()
                ->when(! $request->user()->isOwner(), fn ($q) => $q->where('user_id', $request->user()->id))
                ->select('file_type')
                ->distinct()
                ->orderBy('file_type')
                ->pluck('file_type')
                ->all(),
            'filters' => [
                'file_type' => $validated['file_type'] ?? '',
                'date_from' => $validated['date_from'] ?? '',
                'date_to' => $validated['date_to'] ?? '',
            ],
            'pageTitle' => $request->user()->isOwner() ? 'Riwayat File Sistem' : 'Riwayat File Personal',
            'pageDescription' => $request->user()->isOwner()
                ? 'Owner dapat melihat seluruh file terenkripsi dan melakukan filter dinamis.'
                : 'Staff hanya melihat file terenkripsi miliknya sendiri.',
        ]);
    }

    private function authorizeOwnerFileOnly(Request $request, FileLog $fileLog): void
    {
        abort_unless($fileLog->user_id === $request->user()->id, 403);
    }

    private function authorizeOwnerOrFileOwner(Request $request, FileLog $fileLog): void
    {
        abort_unless($request->user()->isOwner() || $fileLog->user_id === $request->user()->id, 403);
    }

    private function recordCryptoProcess(
        Request $request,
        string $operation,
        float $executionTime,
        ?FileLog $fileLog,
        string $fileName,
        ?int $fileSize,
        ?string $fileType,
    ): void {
        CryptoProcessLog::create([
            'user_id' => $request->user()->id,
            'file_log_id' => $fileLog?->id,
            'operation' => $operation,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'file_type' => $fileType ? strtolower($fileType) : null,
            'execution_time_seconds' => $executionTime,
            'ip_address' => $request->ip(),
        ]);
    }
}
