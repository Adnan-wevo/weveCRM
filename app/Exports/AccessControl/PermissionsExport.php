<?php

namespace App\Exports\AccessControl;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PermissionsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Permission::query()->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Guard Name', 'Created At'];
    }

    /**
     * @param  Permission  $permission
     * @return array<int, mixed>
     */
    public function map($permission): array
    {
        return [
            $permission->id,
            $permission->name,
            $permission->guard_name,
            $permission->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
