@extends('layouts.app')

@section('content')
<section class="panel p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold">Riwayat Audit Seluruh Sistem</h2>
            <p class="mt-1 text-sm text-zinc-600">Owner dapat filter file berdasarkan ekstensi dan rentang tanggal.</p>
        </div>
    </div>
    <form method="GET" class="mt-4 grid gap-3 md:grid-cols-3" id="owner-audit-filter-form">
        <select class="field-input mt-0" name="file_type" id="owner-audit-file-type-filter">
            <option value="">Semua Ekstensi</option>
            @foreach ($extensions as $extension)
                <option value="{{ $extension }}" @selected(($filters['file_type'] ?? '') === $extension)>{{ strtoupper($extension) }}</option>
            @endforeach
        </select>
        <input class="field-input mt-0" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" id="owner-audit-date-from-filter">
        <input class="field-input mt-0" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" id="owner-audit-date-to-filter">
    </form>
    <div class="mt-5">
        @include('partials.log-table', [
            'logs' => $logs,
            'showUser' => true,
            'canDelete' => true,
            'canDecrypt' => true,
            'canUpdatePassword' => false,
        ])
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('owner-audit-filter-form');
    ['owner-audit-file-type-filter', 'owner-audit-date-from-filter', 'owner-audit-date-to-filter'].forEach((id) => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function () {
                filterForm.submit();
            });
        }
    });
});
</script>
@endsection
