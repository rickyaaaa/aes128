<?php

namespace App\Http\Controllers;

use App\Models\FileLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function owner(): View
    {
        $total = FileLog::count();
        $success = FileLog::where('status', FileLog::STATUS_SUCCESS)->count();
        $failed = FileLog::where('status', FileLog::STATUS_FAILED)->count();

        return view('owner.dashboard', [
            'role' => 'owner',
            'pageTitle' => 'Dashboard Owner',
            'pageDescription' => 'Ringkasan global aktivitas pengamanan file untuk kebutuhan audit dan demonstrasi skripsi.',
            'stats' => [
                ['label' => 'Total File Diproses', 'value' => (string) $total, 'tone' => 'text-emerald-700'],
                ['label' => 'Staff Aktif', 'value' => (string) User::where('role', 'staff')->where('is_active', true)->count(), 'tone' => 'text-sky-700'],
                ['label' => 'Audit Berhasil', 'value' => $total > 0 ? round(($success / $total) * 100).'%' : '0%', 'tone' => 'text-lime-700'],
                ['label' => 'Percobaan Gagal', 'value' => (string) $failed, 'tone' => 'text-rose-700'],
            ],
            'activity' => $this->weeklyActivity(),
            'logs' => $this->logRows(FileLog::with('user')->latest()->limit(5)->get()),
        ]);
    }

    public function staff(Request $request): View
    {
        $query = FileLog::forUser($request->user());

        return view('staff.dashboard', [
            'role' => 'staff',
            'pageTitle' => 'Dashboard Staff',
            'pageDescription' => 'Ringkasan personal file yang diproses oleh akun staff.',
            'stats' => [
                'total' => (clone $query)->count(),
                'encryption' => (clone $query)->where('process_type', FileLog::PROCESS_ENCRYPTION)->where('status', FileLog::STATUS_SUCCESS)->count(),
                'decryption' => (clone $query)->where('process_type', FileLog::PROCESS_DECRYPTION)->where('status', FileLog::STATUS_SUCCESS)->count(),
            ],
            'staffLogs' => $this->logRows(FileLog::with('user')->forUser($request->user())->latest()->limit(5)->get()),
        ]);
    }

    private function weeklyActivity(): array
    {
        return collect(range(6, 0))
            ->map(function (int $daysAgo): array {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'label' => $date->isoFormat('ddd'),
                    'count' => FileLog::whereDate('created_at', $date)->count(),
                ];
            })
            ->all();
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
