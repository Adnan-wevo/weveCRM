<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return tap(User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]), function (User $user) {
            // When registering from a tenant URL, link the user to that tenant.
            // tenancy()->initialized covers subdomain mode (POST still on tenant domain).
            // session('registering_tenant_id') covers path mode where the Fortify POST
            // goes to the central domain so tenancy is not initialised there.
            if (tenancy()->initialized) {
                $user->tenants()->attach(tenant('id'));
            } elseif ($tenantId = session()->pull('registering_tenant_id')) {
                $user->tenants()->attach($tenantId);
            }
        });
    }
}
