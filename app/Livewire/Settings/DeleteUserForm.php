<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): mixed
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        // Hold a reference to the user before the session is destroyed by logout.
        $user = Auth::user();

        // Perform the Keycloak + Laravel logout (invalidates session).
        $redirect = $logout();

        $user->delete();

        return $redirect;
    }
}
