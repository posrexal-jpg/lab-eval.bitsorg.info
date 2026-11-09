<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'lab:create-admin {email} {--name=Admin} {--super : Create as super admin}';

    protected $description = 'Create an admin account from the command line (useful if you are locked out)';

    public function handle(): int
    {
        $password = $this->secret('Password (min 10 chars, mixed case, with a number)');

        $v = Validator::make(
            ['email' => $this->argument('email'), 'password' => $password],
            ['email' => 'required|email|unique:users,email', 'password' => ['required', Password::min(10)->mixedCase()->numbers()]]
        );

        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        User::create([
            'name'      => $this->option('name'),
            'email'     => $this->argument('email'),
            'password'  => $password,
            'role'      => $this->option('super') ? 'super_admin' : 'admin',
            'is_active' => true,
        ]);

        $this->info('Admin created.');

        return self::SUCCESS;
    }
}
