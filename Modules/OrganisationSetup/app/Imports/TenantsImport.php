<?php

namespace Modules\OrganisationSetup\Imports;

use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class TenantsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    /** @var array<int, Failure> */
    private array $failures = [];

    public function model(array $row): Tenant
    {
        $data = ['name' => $row['name']];

        if (! empty($row['id'])) {
            $data['id'] = $row['id'];
        }

        return Tenant::create($data);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'id' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/', 'unique:tenants,id'],
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
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'id' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/', 'unique:tenants,id'],
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
        return ['id', 'name'];
    }
}
