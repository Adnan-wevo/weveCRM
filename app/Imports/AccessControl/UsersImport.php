<?php

namespace App\Imports\AccessControl;

use App\Models\User;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class UsersImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    /** @var array<int, Failure> */
    private array $failures = [];

    public function model(array $row): User
    {
        $user = User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => bcrypt($row['password']),
        ]);

        if (! empty($row['roles'])) {
            $roles = array_map('trim', explode(',', (string) $row['roles']));
            $user->syncRoles(array_filter($roles));
        }

        return $user;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
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
        return ['name', 'email', 'password', 'roles'];
    }
}
