<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('apps')]
#[Fillable([
    'name',
    'key',
    'secret',
    'host',
    'port',
    'scheme',
    'allowed_origins',
    'ping_interval',
    'activity_timeout',
    'max_connections',
    'max_message_size',
    'accept_client_events_from',
    'rate_limiting_enabled',
    'rate_limit_max_attempts',
    'rate_limit_decay_seconds',
    'rate_limit_terminate_on_limit',
    'is_active',
])]
class App extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'allowed_origins'               => 'array',
            'port'                          => 'integer',
            'ping_interval'                 => 'integer',
            'activity_timeout'              => 'integer',
            'max_connections'               => 'integer',
            'max_message_size'              => 'integer',
            'rate_limiting_enabled'         => 'boolean',
            'rate_limit_max_attempts'       => 'integer',
            'rate_limit_decay_seconds'      => 'integer',
            'rate_limit_terminate_on_limit' => 'boolean',
            'is_active'                     => 'boolean',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Attributes ───────────────────────────────────────────────────────────

    protected function connectionOptions(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'host'   => $this->host,
                'port'   => $this->port,
                'scheme' => $this->scheme,
                'useTLS' => $this->scheme === 'https',
            ],
        );
    }

    protected function rateLimitingConfig(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'enabled'            => $this->rate_limiting_enabled,
                'max_attempts'       => $this->rate_limit_max_attempts,
                'decay_seconds'      => $this->rate_limit_decay_seconds,
                'terminate_on_limit' => $this->rate_limit_terminate_on_limit,
            ],
        );
    }

    protected function reverbConfig(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'key'                       => $this->key,
                'secret'                    => $this->secret,
                'app_id'                    => $this->id,
                'options'                   => $this->connection_options,
                'allowed_origins'           => $this->allowed_origins ?? ['*'],
                'ping_interval'             => $this->ping_interval,
                'activity_timeout'          => $this->activity_timeout,
                'max_connections'           => $this->max_connections,
                'max_message_size'          => $this->max_message_size,
                'accept_client_events_from' => $this->accept_client_events_from,
                'rate_limiting'             => $this->rate_limiting_config,
            ],
        );
    }
}
