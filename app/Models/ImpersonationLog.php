<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationLog extends BaseModel
{
    /**
     * Central-only table — disable the BelongsToTenant global scope and creating hook.
     */
    public static function bootBelongsToTenant(): void {}

    /**
     * @var list<string>
     */
    protected $fillable = [
        'impersonator_id',
        'impersonated_id',
        'reason',
        'ip_address',
        'user_agent',
        'started_at',
        'ended_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_id');
    }
}
