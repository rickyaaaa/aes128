<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoProcessLog extends Model
{
    use HasFactory;

    public const OPERATION_ENCRYPT = 'encrypt';
    public const OPERATION_DECRYPT = 'decrypt';

    protected $fillable = [
        'user_id',
        'file_log_id',
        'operation',
        'file_name',
        'file_size',
        'file_type',
        'execution_time_seconds',
        'ip_address',
    ];

    protected $casts = [
        'execution_time_seconds' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fileLog(): BelongsTo
    {
        return $this->belongsTo(FileLog::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeEncryptions(Builder $query): Builder
    {
        return $query->where('operation', self::OPERATION_ENCRYPT);
    }

    public function scopeDecryptions(Builder $query): Builder
    {
        return $query->where('operation', self::OPERATION_DECRYPT);
    }

    public function scopeCreatedDuringLocalDate(Builder $query, string $date): Builder
    {
        $start = Carbon::parse($date, config('app.display_timezone'))
            ->startOfDay()
            ->utc();

        return $query->where('created_at', '>=', $start)
            ->where('created_at', '<', $start->copy()->addDay());
    }
}
