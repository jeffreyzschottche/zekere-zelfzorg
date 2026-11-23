<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CareDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-23 21:00', 'Europe/Amsterdam'));
        $this->seed(CareDemoSeeder::class);
    }

    private function loginAs(string $alias): void
    {
        Sanctum::actingAs(User::whereEmail("$alias@example.test")->firstOrFail());
    }

    public function test_membership_and_professional_planning_permissions(): void
    {
        $this->getJson('/api/v1/care/profiles')->assertUnauthorized();
        $this->loginAs('sam');
        $profile = $this->getJson('/api/v1/care/profiles')->assertOk()->assertJsonCount(1)->json('0.id');
        $today = "/api/v1/care/profiles/$profile/today";
        $assignments = "/api/v1/care/profiles/$profile/assignments";
        $this->getJson($today)->assertOk()->assertJsonPath('tasks.0.can_execute', true);
        $data = ['assignee_id' => User::whereEmail('noor@example.test')->value('id'),
            'starts_on' => '2026-11-23', 'time' => '22:00', 'repeat' => 'alternate'];
        $this->postJson($assignments, $data)->assertForbidden();
        $this->loginAs('robin');
        $this->postJson($assignments, $data)->assertCreated();
        $this->getJson($today)->assertJsonCount(2, 'tasks');
        $this->travel(1)->days();
        $this->getJson($today)->assertJsonCount(1, 'tasks');
        Sanctum::actingAs(User::factory()->create());
        $this->getJson($today)->assertNotFound();
        $this->getJson('/api/v1/care/profiles')->assertExactJson([]);
    }

    public function test_an_execution_is_attributed_to_the_assignee_and_cannot_be_overwritten(): void
    {
        $profile = DB::table('care_profiles')->value('id');
        $assignment = DB::table('care_assignments')->value('id');
        $path = "/api/v1/care/profiles/$profile/assignments/$assignment/2026-11-22";
        $this->loginAs('noor');
        $this->getJson($path)->assertForbidden();
        $this->loginAs('sam');
        $version = $this->getJson($path)->assertOk()->json('version');
        $payload = [
            'status' => 'performed', 'version' => $version, 'answers' => ['doubt' => 'no'],
            'performed_at' => '2026-11-22T19:07:00.000Z', 'opened_steps' => ['prepare'],
        ];
        $this->postJson($path, $payload)->assertCreated();
        $this->postJson($path, $payload)->assertConflict();
        $this->loginAs('alex');
        $this->getJson("/api/v1/care/profiles/$profile/log")->assertOk()
            ->assertJsonPath('0.recorder', 'Sam Jansen')->assertJsonPath('0.version', $version)
            ->assertJsonPath('0.performed_at', '2026-11-22T19:07:00+00:00');
        $this->getJson("/api/v1/care/profiles/$profile/today?day=2026-11-22")->assertOk()
            ->assertJsonPath('tasks.0.status', 'performed');
    }

    public function test_followup_due_time_permissions_and_simulated_alert(): void
    {
        $profile = DB::table('care_profiles')->value('id');
        $assignment = DB::table('care_assignments')->value('id');
        $base = "/api/v1/care/profiles/$profile";
        $this->loginAs('sam');
        $this->postJson("$base/assignments/$assignment/2026-11-23", [
            'status' => 'performed', 'version' => 'demo-1', 'answers' => ['doubt' => 'no'],
        ])->assertCreated();
        $check = $this->getJson("$base/followups")->assertOk()->assertJsonPath('0.is_due', false)
            ->assertJsonPath('0.due_at', '2026-11-23T20:15:00+00:00')->json('0.id');
        $this->postJson("$base/followups/$check", ['answer' => 'yes'])->assertConflict();
        $this->travel(15)->minutes();
        $this->loginAs('noor');
        $this->postJson("$base/followups/$check", ['answer' => 'yes'])->assertForbidden();
        $this->loginAs('alex');
        $this->postJson("$base/followups/$check", ['answer' => 'yes'])->assertOk()
            ->assertExactJson(['deviation' => true, 'simulated' => true]);
        $this->postJson("$base/followups/$check", ['answer' => 'no'])->assertConflict();
        $this->getJson("$base/followups")->assertJsonPath('0.observer', 'Alex de Vries')
            ->assertJsonPath('0.deviation', true);
        $this->assertDatabaseCount('care_alerts', 1);
    }

    public function test_reviews_and_individual_skill_assessments_require_a_professional(): void
    {
        $profile = DB::table('care_profiles')->value('id');
        $assignment = DB::table('care_assignments')->value('id');
        $base = "/api/v1/care/profiles/$profile";
        $this->loginAs('sam');
        $this->postJson("$base/assignments/$assignment/2026-11-23", [
            'status' => 'performed', 'version' => 'demo-1', 'answers' => ['doubt' => 'yes'],
        ])->assertCreated();
        $entry = DB::table('care_executions')->value('id');
        $skill = ['user_id' => User::whereEmail('sam@example.test')->value('id'),
            'status' => 'demonstrated', 'method' => 'demo_observation', 'note' => 'Oefenscenario getoond.'];
        $review = ['outcome' => 'followup', 'note' => 'In de demo opnieuw bespreken.'];
        $this->getJson("$base/reviews")->assertForbidden();
        $this->postJson("$base/reviews/$entry", $review)->assertForbidden();
        $this->postJson("$base/skills", $skill)->assertForbidden();
        $this->loginAs('robin');
        $this->getJson("$base/reviews")->assertOk()->assertJsonPath('alerts.0.outcome', null);
        $this->postJson("$base/reviews/$entry", $review)->assertCreated();
        $this->postJson("$base/skills", $skill)->assertCreated();
        $this->postJson("$base/reviews/$entry", $review)->assertConflict();
        $this->getJson("$base/reviews")->assertJsonPath('alerts.0.outcome', 'followup')
            ->assertJsonPath('reviews.0.reviewer', 'Robin Visser');
        $this->travel(15)->minutes();
        $this->loginAs('alex');
        $check = DB::table('care_followups')->value('id');
        $this->postJson("$base/followups/$check", ['answer' => 'yes'])->assertOk();
        $this->loginAs('robin');
        $this->getJson("$base/reviews")->assertJsonPath('alerts.0.outcome', null)
            ->assertJsonPath('alerts.1.outcome', 'followup');
        $this->postJson("$base/reviews/$entry", $review)->assertCreated();
        $this->getJson("$base/reviews")->assertJsonPath('alerts.0.outcome', 'followup');
        $this->loginAs('noor');
        $this->getJson("$base/skills")->assertJsonCount(1)->assertJsonPath('0.status', null);
        $this->loginAs('sam');
        $this->getJson("$base/skills")->assertJsonCount(1)->assertJsonPath('0.status', 'demonstrated');
    }
}
