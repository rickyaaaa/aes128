<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidSecretKeyException;
use App\Http\Requests\DecryptFileRequest;
use App\Http\Requests\EncryptFileRequest;
use App\Models\FileLog;
use App\Services\FileCryptoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FileProcessController extends Controller
{
    public function createEncryption(Request $request): View
    {
        return view('files.encrypt', [
            'role' => $request->user()->role,
            'pageTitle' => 'Enkripsi File',
            'pageDescription' => 'Unggah file JPG, PNG, atau PDF dan masukkan Kunci Rahasia untuk pengamanan AES-128-CBC.',
        ]);
    }

    public function storeEncryption(EncryptFileRequest $request, FileCryptoService $service): RedirectResponse
    {
        $file = $request->file('source_file');

        try {
            $result = $service->encrypt($file, $request->string('secret_key')->toString());
            $log = $this->writeLog($request, $file, FileLog::PROCESS_ENCRYPTION, $result);

            return back()
                ->with('status', $result['message'])
                ->with('output_filename', $result['output_filename'])
                ->with('download_url', route('files.download', $log));
        } catch (Throwable $exception) {
            $this->writeLog($request, $file, FileLog::PROCESS_ENCRYPTION, [
                'status' => FileLog::STATUS_FAILED,
                'output_filename' => null,
                'stored_path' => null,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['source_file' => 'Proses enkripsi gagal.']);
        }
    }

    public function createDecryption(Request $request): View
    {
        return view('files.decrypt', [
            'role' => $request->user()->role,
            'pageTitle' => 'Dekripsi File',
            'pageDescription' => 'Unggah file .enc dan gunakan Kunci Rahasia yang sama untuk membuka file asli.',
        ]);
    }

    public function storeDecryption(DecryptFileRequest $request, FileCryptoService $service): RedirectResponse
    {
        $file = $request->file('encrypted_file');

        try {
            $result = $service->decrypt($file, $request->string('secret_key')->toString());
            $log = $this->writeLog($request, $file, FileLog::PROCESS_DECRYPTION, $result, $result['file_type'] ?? 'enc');

            return back()
                ->with('status', $result['message'])
                ->with('output_filename', $result['output_filename'])
                ->with('download_url', route('files.download', $log));
        } catch (InvalidSecretKeyException $exception) {
            $this->writeLog($request, $file, FileLog::PROCESS_DECRYPTION, [
                'status' => FileLog::STATUS_FAILED,
                'output_filename' => null,
                'stored_path' => null,
                'message' => $exception->getMessage(),
            ], 'enc');

            return back()->withErrors(['encrypted_file' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            $this->writeLog($request, $file, FileLog::PROCESS_DECRYPTION, [
                'status' => FileLog::STATUS_FAILED,
                'output_filename' => null,
                'stored_path' => null,
                'message' => $exception->getMessage(),
            ], 'enc');

            return back()->withErrors(['encrypted_file' => 'Proses dekripsi gagal.']);
        }
    }

    public function download(Request $request, FileLog $fileLog): StreamedResponse
    {
        abort_unless($request->user()->isOwner() || $fileLog->user_id === $request->user()->id, 403);
        abort_if(! $fileLog->stored_path || ! Storage::disk('local')->exists($fileLog->stored_path), 404);

        return Storage::disk('local')->download($fileLog->stored_path, $fileLog->output_filename);
    }

    public function history(Request $request): View
    {
        $query = FileLog::with('user')->latest();

        if ($request->user()->isStaff()) {
            $query->forUser($request->user());
        }

        $logs = $this->logRows($query->paginate(20)->getCollection());

        return view('history', [
            'role' => $request->user()->role,
            'visibleLogs' => $logs,
            'pageTitle' => $request->user()->isOwner() ? 'Riwayat File' : 'Riwayat File Saya',
            'pageDescription' => $request->user()->isOwner() ? 'Tampilan ringkas semua proses file.' : 'Riwayat enkripsi dan dekripsi milik akun staff.',
        ]);
    }

    private function writeLog(Request $request, UploadedFile $file, string $processType, array $result, ?string $fileType = null): FileLog
    {
        return FileLog::create([
            'user_id' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $fileType ?? strtolower($file->getClientOriginalExtension()),
            'process_type' => $processType,
            'file_size_kb' => max(1, (int) ceil($file->getSize() / 1024)),
            'status' => $result['status'] ?? FileLog::STATUS_SUCCESS,
            'ip_address' => $request->ip(),
            'output_filename' => $result['output_filename'] ?? null,
            'stored_path' => $result['stored_path'] ?? null,
            'error_message' => ($result['status'] ?? FileLog::STATUS_SUCCESS) === FileLog::STATUS_FAILED ? ($result['message'] ?? null) : null,
        ]);
    }

    private function logRows($logs): array
    {
        return $logs->map(fn (FileLog $log): array => [
            'user' => $log->user?->name ?? 'User dihapus',
            'filename' => $log->original_filename,
            'type' => $log->file_type,
            'process' => $log->process_type,
            'size' => number_format($log->file_size_kb, 0, ',', '.').' KB',
            'status' => $log->status,
            'ip' => $log->ip_address ?? '-',
            'created_at' => $log->created_at?->format('d M Y, H:i') ?? '-',
            'download_url' => $log->stored_path ? route('files.download', $log) : null,
        ])->all();
    }
}
