<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:set-role {email} {role : employee oder asset_manager}')]
#[Description('Setzt die Web-Berechtigung eines bestehenden Benutzers')]
class SetUserRole extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $role = $this->argument('role');

        if (! in_array($role, [User::ROLE_EMPLOYEE, User::ROLE_ASSET_MANAGER], true)) {
            $this->error('Erlaubte Rollen: employee, asset_manager.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Benutzer nicht gefunden.');

            return self::FAILURE;
        }

        $user->role = $role;
        $user->save();
        $this->info('Benutzerrolle aktualisiert.');

        return self::SUCCESS;
    }
}
