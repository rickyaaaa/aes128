<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\FileLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
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
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }
        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        return view('owner.audit-logs', [
            'role' => 'owner',
            'pageTitle' => 'Audit Log Global',
            'pageDescription' => 'Owner dapat memonitor seluruh file terenkripsi dari semua user.',
            'logs' => $query->paginate(50)->withQueryString()->items(),
            'extensions' => FileLog::query()->select('file_type')->distinct()->orderBy('file_type')->pluck('file_type')->all(),
            'filters' => [
                'file_type' => $validated['file_type'] ?? '',
                'date_from' => $validated['date_from'] ?? '',
                'date_to' => $validated['date_to'] ?? '',
            ],
        ]);
    }
}
