<?php

namespace App\Care;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class Followups
{
    public const QUESTIONS = [
        'observation' => 'Is er in dit oefenscenario iets opgevallen?',
        'support' => 'Zou je in dit oefenscenario extra uitleg willen?',
    ];
    public const ANSWERS = ['no' => 'Nee', 'yes' => 'Ja', 'unknown' => 'Weet ik niet'];

    public function onExecution(int $id, object $assignment, CarbonImmutable $now, ?CarbonImmutable $performed, ?string $doubt): void
    {
        if ($assignment->followup_minutes !== null) {
            $due = ($performed ?? $now)->addMinutes($assignment->followup_minutes);
            DB::table('care_followups')->insert([
                'care_execution_id' => $id, 'due_at' => $due,
                'question' => $assignment->followup_question, 'trigger' => $assignment->followup_trigger,
                'responder' => $assignment->followup_responder,
            ]);
        }
        if (in_array($doubt, ['yes', 'unknown'], true)) {
            $this->flag($id, 'checklist', $now);
        }
    }

    public function flag(int $execution, string $source, CarbonImmutable $now): void
    {
        DB::table('care_alerts')->insert([
            'care_execution_id' => $execution, 'source' => $source, 'created_at' => $now,
        ]);
    }

    public function mayAnswer(object $check, string $role, int $user): bool
    {
        $executor = $check->recorded_by === $user && in_array($role, ['caregiver', 'patient'], true);
        return match ($check->responder) {
            'either' => $role === 'patient' || $executor,
            'patient' => $role === 'patient',
            'executor' => $executor,
            default => false,
        };
    }
}
