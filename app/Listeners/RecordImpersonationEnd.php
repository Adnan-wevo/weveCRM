<?php

namespace App\Listeners;

use App\Models\ImpersonationLog;
use App\Models\User;
use Lab404\Impersonate\Events\LeaveImpersonation;

class RecordImpersonationEnd
{
    public function handle(LeaveImpersonation $event): void
    {
        /** @var User $impersonator */
        $impersonator = $event->impersonator;

        /** @var User $impersonated */
        $impersonated = $event->impersonated;

        ImpersonationLog::query()
            ->where('impersonator_id', $impersonator->id)
            ->where('impersonated_id', $impersonated->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first()
            ?->update(['ended_at' => now()]);
    }
}
