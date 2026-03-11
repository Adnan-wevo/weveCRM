<?php

namespace Modules\OrganisationSetup\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantRecordChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly ?string $tenantId;

    public function __construct(?string $tenantId = null)
    {
        // Tenants are central data but can be accessed from a tenant context too.
        $this->tenantId = $tenantId ?? (tenancy()->initialized ? tenant('id') : null);
    }

    public function broadcastOn(): array
    {
        $suffix = $this->tenantId ? ".{$this->tenantId}" : '.central';

        return [new Channel("organisation-setup.tenants{$suffix}")];
    }

    public function broadcastAs(): string
    {
        return 'TenantRecordChanged';
    }
}
