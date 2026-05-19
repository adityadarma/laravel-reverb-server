<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReverbMetric extends Model
{
    public $timestamps = false;

    protected $fillable = ['app_id', 'connections', 'channels', 'recorded_at'];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Get metrics for the last N minutes, grouped by minute.
     */
    public static function lastMinutes(string $appId, int $minutes = 60): array
    {
        $rows = static::query()
            ->where('app_id', $appId)
            ->where('recorded_at', '>=', now()->subMinutes($minutes))
            ->orderBy('recorded_at')
            ->get(['connections', 'channels', 'recorded_at']);

        return $rows->map(fn ($r) => [
            'time' => $r->recorded_at->format('H:i'),
            'connections' => $r->connections,
            'channels' => $r->channels,
        ])->values()->all();
    }

    /**
     * Prune old metrics (keep last 24 hours).
     */
    public static function prune(): void
    {
        static::where('recorded_at', '<', now()->subHours(24))->delete();
    }
}
