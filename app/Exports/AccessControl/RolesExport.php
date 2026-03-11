<?php

namespace App\Exports\AccessControl;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RolesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Role::query()->with('permissions')->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Guard Name', 'Permissions', 'Created At'];
    }

    /**
     * @param  Role  $role
     * @return array<int, mixed>
     */
    public function map($role): array
    {
        return [
            $role->id,
            $role->name,
            $role->guard_name,
            $role->permissions->pluck('name')->implode(', '),
            $role->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
