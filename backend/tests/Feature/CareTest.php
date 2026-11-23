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
}
