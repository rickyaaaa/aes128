<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FileLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'stored_path',
        'file_size',
        'file_type',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cryptoProcessLogs(): HasMany
    {
        return $this->hasMany(CryptoProcessLog::class);
    }

    public function latestEncryptionProcess(): HasOne
    {
        return $this->hasOne(CryptoProcessLog::class)
            ->where('operation', CryptoProcessLog::OPERATION_ENCRYPT)
            ->latest('id');
    }

    public function latestDecryptionProcess(): HasOne
    {
        return $this->hasOne(CryptoProcessLog::class)
            ->where('operation', CryptoProcessLog::OPERATION_DECRYPT)
            ->latest('id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeCreatedOnOrAfterLocalDate(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '>=', $this->localDayStartUtc($date));
    }

    public function scopeCreatedBeforeOrOnLocalDate(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '<', $this->localDayStartUtc($date)->addDay());
    }

    public function scopeCreatedDuringLocalDate(Builder $query, string $date): Builder
    {
        return $query
            ->createdOnOrAfterLocalDate($date)
            ->createdBeforeOrOnLocalDate($date);
    }

    private function localDayStartUtc(string $date): Carbon
    {
        return Carbon::parse($date, config('app.display_timezone'))
            ->startOfDay()
            ->utc();
    }
}
