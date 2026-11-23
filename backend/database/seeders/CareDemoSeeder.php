<?php

namespace Database\Seeders;

use App\Care\Planning;
use App\Care\Templates;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CareDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local', 'testing')) {
            throw new \RuntimeException('De demoaccounts zijn alleen bedoeld voor local en testing.');
        }

        DB::transaction(function () {
            $profile = DB::table('care_profiles')->where('name', 'Alex de Vries')->value('id')
                ?? DB::table('care_profiles')->insertGetId(['name' => 'Alex de Vries']);

            foreach ([
                ['alex', 'Alex de Vries', 'patient'],
                ['sam', 'Sam Jansen', 'caregiver'],
                ['noor', 'Noor Bakker', 'caregiver'],
                ['robin', 'Robin Visser', 'professional'],
            ] as [$alias, $name, $role]) {
                $user = User::firstOrCreate(['email' => "$alias@example.test"], [
                    'name' => $name, 'password' => Hash::make('ZekereDemo2026!'),
                ]);
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
                DB::table('care_members')->upsert(
                    ['care_profile_id' => $profile, 'user_id' => $user->id, 'role' => $role],
                    ['care_profile_id', 'user_id'], ['role']
                );
                if ($alias === 'sam') {
                    DB::table('care_assignments')->updateOrInsert(
                        ['care_profile_id' => $profile, 'assignee_id' => $user->id, 'template' => Templates::NIGHT_BAG, 'time' => '20:00'],
                        ['starts_on' => CarbonImmutable::now(Planning::ZONE)->subWeek()->toDateString(), 'repeat' => 'daily']
                    );
                }
            }
        });
    }
}
