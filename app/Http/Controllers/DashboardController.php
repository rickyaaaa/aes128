<?php

namespace App\Http\Controllers;

use App\Models\FileLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function owner(Request $request): View
    {
        $validated = $request->validate([
            'file_type' => ['nullable', 'string', 'max:32'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = FileLog::with('user')->latest();

        if (! empty($validated['file_type'])) {
            $query->where('file_type', $validated['file_type']);
        }
        if (! empty($validated['date_from'])) {
            $query->createdOnOrAfterLocalDate($validated['date_from']);
        }
        if (! empty($validated['date_to'])) {
            $query->createdBeforeOrOnLocalDate($validated['date_to']);
        }

        $totalFiles = FileLog::count();
        $totalBytes = FileLog::sum('file_size');

        return view('owner.dashboard', [
            'role' => 'owner',
            'pageTitle' => 'Dashboard Owner',
            'pageDescription' => 'Owner melihat seluruh file terenkripsi dan dapat memfilter berdasarkan ekstensi serta tanggal.',
            'stats' => [
                ['label' => 'Total File Terenkripsi', 'value' => (string) $totalFiles, 'tone' => 'text-emerald-700'],
                ['label' => 'Staff Aktif', 'value' => (string) User::where('role', 'staff')->where('is_active', true)->count(), 'tone' => 'text-sky-700'],
                ['label' => 'Total Ukuran File', 'value' => $this->formatBytes((int) $totalBytes), 'tone' => 'text-lime-700'],
                ['label' => 'Upload Hari Ini', 'value' => (string) FileLog::createdDuringLocalDate($this->todayInDisplayTimezone()->toDateString())->count(), 'tone' => 'text-amber-700'],
            ],
            'activity' => $this->weeklyActivity(),
            'logs' => $query->limit(20)->get(),
            'extensions' => FileLog::query()->select('file_type')->distinct()->orderBy('file_type')->pluck('file_type')->all(),
            'filters' => [
                'file_type' => $validated['file_type'] ?? '',
                'date_from' => $validated['date_from'] ?? '',
                'date_to' => $validated['date_to'] ?? '',
            ],
        ]);
    }

    public function staff(Request $request): View
    {
        $query = FileLog::with('user')->forUser($request->user())->latest();
        $totalBytes = (int) (clone $query)->sum('file_size');

        return view('staff.dashboard', [
            'role' => 'staff',
            'pageTitle' => 'Dashboard Staff',
            'pageDescription' => 'Staff hanya dapat mengakses file terenkripsi miliknya sendiri.',
            'stats' => [
                'total' => (clone $query)->count(),
                'size' => $this->formatBytes($totalBytes),
                'today' => (clone $query)->createdDuringLocalDate($this->todayInDisplayTimezone()->toDateString())->count(),
            ],
            'staffLogs' => (clone $query)->limit(20)->get(),
        ]);
    }

    private function weeklyActivity(): array
    {
        return collect(range(6, 0))
            ->map(function (int $daysAgo): array {
                $date = $this->todayInDisplayTimezone()->subDays($daysAgo);

                return [
                    'label' => $date->isoFormat('ddd'),
                    'count' => FileLog::createdDuringLocalDate($date->toDateString())->count(),
                ];
            })
            ->all();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return number_format($bytes / 1048576, 1, ',', '.').' MB';
    }

    private function todayInDisplayTimezone(): Carbon
    {
        return Carbon::today(config('app.display_timezone'));
    }
}
