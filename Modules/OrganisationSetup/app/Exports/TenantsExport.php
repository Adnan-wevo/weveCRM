<?php

namespace Modules\OrganisationSetup\Exports;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TenantsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Tenant::query()->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Created At'];
    }

    /**
     * @param  Tenant  $tenant
     * @return array<int, mixed>
     */
    public function map($tenant): array
    {
        return [
            $tenant->id,
            $tenant->name,
            $tenant->created_at?->format('Y-m-d H:i:s'), // @phpstan-ignore nullsafe.neverNull
        ];
    }
}
