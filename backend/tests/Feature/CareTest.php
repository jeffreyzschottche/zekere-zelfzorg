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
}
