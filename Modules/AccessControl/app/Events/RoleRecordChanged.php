<?php

namespace Modules\AccessControl\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleRecordChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly ?string $tenantId;

    public function __construct(?string $tenantId = null)
    {
        $this->tenantId = $tenantId ?? (tenancy()->initialized ? tenant('id') : null);
    }

    public function broadcastOn(): array
    {
        $suffix = $this->tenantId ? ".{$this->tenantId}" : '.central';

        return [new Channel("access-control.roles{$suffix}")];
    }

    public function broadcastAs(): string
    {
        return 'RoleRecordChanged';
    }
}
