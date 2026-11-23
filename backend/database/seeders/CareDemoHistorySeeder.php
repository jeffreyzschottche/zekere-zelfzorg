<?php

namespace Database\Seeders;

use App\Care\Followups;
use App\Care\Planning;
use App\Care\Templates;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CareDemoHistorySeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CareDemoSeeder::class);

        DB::transaction(function () {
            $sam = DB::table('users')->where('email', 'sam@example.test')->value('id');
            $robin = DB::table('users')->where('email', 'robin@example.test')->value('id');
            $assignment = DB::table('care_assignments')->where('assignee_id', $sam)
                ->where('time', '20:00')->where('template', Templates::NIGHT_BAG)->firstOrFail();
            $version = app(Templates::class)->instruction(Templates::NIGHT_BAG)['version'];
            $today = CarbonImmutable::now(Planning::ZONE)->startOfDay();

            foreach ([2 => 'yes', 1 => 'no'] as $daysAgo => $doubt) {
                $planned = $today->subDays($daysAgo)->setHour(20);
                $actual = $planned->utc()->addMinutes(2);
                $recorded = $daysAgo === 1 ? $today->setHour(8)->utc()->min(CarbonImmutable::now()) : $actual;
                $id = DB::table('care_executions')->insertOrIgnore([
                    'care_assignment_id' => $assignment->id, 'recorded_by' => $sam,
                    'day' => $planned->toDateString(), 'status' => 'performed', 'version' => $version,
                    'answers' => json_encode(['doubt' => $doubt], JSON_THROW_ON_ERROR),
                    'opened_steps' => json_encode(['prepare', 'guide', 'finish'], JSON_THROW_ON_ERROR),
                    'note' => 'Voorbeeldregistratie met fictieve gegevens.', 'performed_at' => $actual,
                    'recorded_at' => $recorded, 'retrospective' => $daysAgo === 1,
                ]);
                if ($id) {
                    $execution = DB::table('care_executions')->where('care_assignment_id', $assignment->id)
                        ->where('day', $planned->toDateString())->value('id');
                    app(Followups::class)->onExecution($execution, $assignment, $recorded, $actual, $doubt);
                }
            }

            if (!DB::table('care_skills')->where('care_profile_id', $assignment->care_profile_id)->where('user_id', $sam)->exists()) {
                DB::table('care_skills')->insert([
                    'care_profile_id' => $assignment->care_profile_id, 'user_id' => $sam, 'reviewed_by' => $robin,
                    'template' => Templates::NIGHT_BAG, 'version' => $version, 'status' => 'demonstrated',
                    'method' => 'demo_observation', 'note' => 'Sam heeft de app in een fictieve oefensituatie doorlopen.',
                    'reviewed_at' => $today->subDays(2)->setHour(12)->utc(),
                ]);
            }
        });
    }
}
