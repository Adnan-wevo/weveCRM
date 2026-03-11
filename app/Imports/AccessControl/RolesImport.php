<?php

namespace App\Imports\AccessControl;

use App\Models\Role;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class RolesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    /** @var array<int, Failure> */
    private array $failures = [];

    public function model(array $row): Role
    {
        $role = Role::create([
            'name' => $row['name'],
            'guard_name' => $row['guard_name'] ?? 'web',
        ]);

        if (! empty($row['permissions'])) {
            $perms = array_map('trim', explode(',', (string) $row['permissions']));
            $role->syncPermissions(array_filter($perms));
        }

        // @phpstan-ignore return.type
        return $role;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function onFailure(Failure ...$failures): void
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    /**
     * @return array<int, Failure>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Validate a single row array against the import rules.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, array<int, string>> Validation errors keyed by field name.
     */
    public static function validateRow(array $row, int $rowIndex): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($row, [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['required', 'string', 'max:255'],
        ]);

        return $validator->errors()->toArray();
    }

    /**
     * Required headings for the import file.
     *
     * @return array<int, string>
     */
    public static function headings(): array
    {
        return ['name', 'guard_name', 'permissions'];
    }
}
