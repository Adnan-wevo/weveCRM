<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin
                            {--name= : Full name of the super-admin}
                            {--email= : Email address of the super-admin}
                            {--password= : Password for the super-admin}';

    protected $description = 'Create a new super-admin user interactively';

    public function handle(): int
    {
        intro('Create Super Admin');

        $name = text(
            label: 'Full name',
            placeholder: 'e.g. Ahmad Nizam',
            default: $this->option('name') ?? '',
            required: 'Name is required.',
        );

        $email = text(
            label: 'Email address',
            placeholder: 'e.g. admin@example.com',
            default: $this->option('email') ?? '',
            required: 'Email is required.',
            validate: function (string $value): ?string {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'Please enter a valid email address.';
                }

                if (User::withTrashed()->where('email', $value)->exists()) {
                    return 'A user with this email already exists.';
                }

                return null;
            },
        );

        // If --password option is passed (non-interactive mode), use it directly.
        // Otherwise prompt interactively — password() has no $default parameter.
        $plainPassword = $this->option('password') ?? password(
            label: 'Password',
            placeholder: 'Min. 8 characters',
            required: 'Password is required.',
            validate: fn (string $value): ?string => strlen($value) < 8
                ? 'Password must be at least 8 characters.'
                : null,
        );

        $confirmed = confirm(
            label: "Create super-admin for {$email}?",
            default: true,
        );

        if (! $confirmed) {
            error('Aborted. No user was created.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'email_verified_at' => now(),
        ]);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user->syncRoles(['super-admin']);

        outro("Super-admin \"{$user->name}\" ({$user->email}) created successfully.");

        return self::SUCCESS;
    }
}
