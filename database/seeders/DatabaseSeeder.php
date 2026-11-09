<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\Room;
use App\Models\User;
use App\Services\TemplateParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // First super admin. Set SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD in .env, or a random password is printed.
        $email = env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Super Admin', 'password' => $password, 'role' => 'super_admin', 'is_active' => true]
        );
        if ($user->wasRecentlyCreated) {
            $this->command?->warn("Super admin created: {$email} / {$password}  (change this password after first login)");
        }

        // The form you supplied, as the first (active) template.
        if (FormTemplate::count() === 0) {
            $parsed = app(TemplateParser::class)->process(file_get_contents(__DIR__.'/default_template.html'));
            FormTemplate::create([
                'name'        => 'Computer Laboratory User Satisfaction Form',
                'version'     => 1,
                'html'        => $parsed['html'],
                'schema'      => $parsed['schema'],
                'is_active'   => true,
                'uploaded_by' => $user->id,
            ]);
        }

        if (Room::count() === 0) {
            foreach (['Computer Laboratory 1', 'Computer Laboratory 2', 'Computer Laboratory 3'] as $name) {
                Room::create(['name' => $name, 'building' => 'Main Building', 'capacity' => 40, 'is_active' => true]);
            }
        }
    }
}
