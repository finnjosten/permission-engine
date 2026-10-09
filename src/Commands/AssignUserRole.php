<?php

namespace Finn\PermissionEngine\Commands;

use Finn\PermissionEngine\Commands\Concerns\ResolvesRoles;
use Finn\PermissionEngine\Models\Role;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    use ResolvesRoles;

    protected $signature = 'user:role
                            {user? : User email or id}
                            {role? : Role name or id, or "none" to remove the role}';

    protected $description = 'Assign a role to a user';

    public function handle(): int
    {
        $userModel = config('permission-engine.user_model', 'App\\Models\\User');

        $identifier = trim((string) ($this->argument('user') ?? $this->ask('User email or id?')));

        if ($identifier === '') {
            $this->error('A user email or id is required.');
            return self::FAILURE;
        }

        $user = ctype_digit($identifier)
            ? $userModel::find((int) $identifier)
            : $userModel::where('email', $identifier)->first();

        if (!$user) {
            $this->error("User '{$identifier}' not found.");
            return self::FAILURE;
        }

        $value = $this->argument('role');

        if ($value === null) {
            if (!$this->input->isInteractive()) {
                $this->error('A role (or "none") is required when running non-interactively.');
                return self::FAILURE;
            }

            $options = Role::orderBy('name')->pluck('name')->prepend('none')->all();
            $value = $this->choice("Which role should {$user->email} get?", $options, 0);
        }

        if (strtolower($value) === 'none') {
            $user->role()->dissociate()->save();
            $this->info("Removed the role from {$user->email}.");
            return self::SUCCESS;
        }

        $role = $this->findRole($value);

        if (!$role) {
            $this->error("Role '{$value}' not found.");
            return self::FAILURE;
        }

        $user->role()->associate($role)->save();
        $this->info("{$user->email} now has the role '{$role->name}'.");

        return self::SUCCESS;
    }
}
