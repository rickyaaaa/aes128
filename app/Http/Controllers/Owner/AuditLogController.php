<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\FileLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('owner.audit-logs', [
            'role' => 'owner',
            'pageTitle' => 'Audit Log Global',
            'pageDescription' => 'Seluruh riwayat proses file untuk analisis keamanan dan aktivitas sistem.',
            'logs' => FileLog::with('user')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (FileLog $log): array => [
                    'user' => $log->user?->name ?? 'User dihapus',
                    'filename' => $log->original_filename,
                    'type' => $log->file_type,
                    'process' => $log->process_type,
                    'size' => number_format($log->file_size_kb, 0, ',', '.').' KB',
                    'status' => $log->status,
                    'ip' => $log->ip_address ?? '-',
                    'created_at' => $log->created_at?->format('d M Y, H:i') ?? '-',
                    'download_url' => $log->stored_path ? route('files.download', $log) : null,
                ])
                ->all(),
        ]);
    }
}
