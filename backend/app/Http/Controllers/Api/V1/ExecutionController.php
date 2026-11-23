<?php

namespace App\Http\Controllers\Api\V1;

use App\Care\CareAccess;
use App\Care\Planning;
use App\Care\Templates;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExecutionController
{
    public function __construct(private CareAccess $access, private Planning $planning, private Templates $templates) {}

    private function assignment(Request $request, int $profile, int $assignment, string $day): object
    {
        $role = $this->access->role($profile, $request->user()->id);
        $record = DB::table('care_assignments')->where('care_profile_id', $profile)->find($assignment);
        abort_unless($record, 404);
        abort_unless(in_array($role, ['patient', 'caregiver'], true) && $record->assignee_id === $request->user()->id, 403);
        $date = validator(['day' => $day], ['day' => 'required|date_format:Y-m-d'])->validate()['day'];
        abort_unless($date <= CarbonImmutable::now(Planning::ZONE)->toDateString() && $this->planning->scheduledAt($record, $date), 404);
        return $record;
    }

    public function show(Request $request, int $profile, int $assignment, string $day): array
    {
        $record = $this->assignment($request, $profile, $assignment, $day);
        return $this->templates->instruction($record->template);
    }

    public function store(Request $request, int $profile, int $assignment, string $day)
    {
        $record = $this->assignment($request, $profile, $assignment, $day);
        $instruction = $this->templates->instruction($record->template);
        $data = $request->validate([
            'status' => ['required', Rule::in(['performed', 'not_performed', 'unknown'])],
            'version' => ['required', Rule::in([$instruction['version']])],
            'answers' => ['required_if:status,performed', 'array:doubt'],
            'answers.doubt' => ['required_if:status,performed', Rule::in(['yes', 'no', 'unknown'])],
            'opened_steps' => ['nullable', 'array', 'max:3'],
            'opened_steps.*' => ['distinct', Rule::in(array_column($instruction['steps'], 'id'))],
            'note' => ['nullable', 'string', 'max:1000'],
            'performed_at' => ['nullable', 'exclude_unless:status,performed', 'date_format:Y-m-d\TH:i:s.v\Z'],
        ]);
        $now = CarbonImmutable::now();
        $retrospective = $day < $now->setTimezone(Planning::ZONE)->toDateString();
        $performed = isset($data['performed_at']) ? CarbonImmutable::parse($data['performed_at'])->utc() : null;
        abort_if($performed && ($performed->gt($now) || $performed->setTimezone(Planning::ZONE)->toDateString() !== $day), 422, 'Kies een uitvoertijd op de geplande dag die al voorbij is.');

        $inserted = DB::table('care_executions')->insertOrIgnore([
            'care_assignment_id' => $record->id, 'recorded_by' => $request->user()->id,
            'day' => $day, 'status' => $data['status'], 'version' => $instruction['version'],
            'answers' => json_encode($data['answers'] ?? [], JSON_THROW_ON_ERROR),
            'opened_steps' => json_encode($data['opened_steps'] ?? [], JSON_THROW_ON_ERROR),
            'note' => $data['note'] ?? null,
            'performed_at' => $data['status'] === 'performed' ? ($performed ?? ($retrospective ? null : $now)) : null,
            'recorded_at' => $now, 'retrospective' => $retrospective,
        ]);
        abort_unless($inserted, 409, 'Er staat al een registratie bij deze uitvoering.');
        return response()->json(['saved' => true], 201);
    }

    public function log(Request $request, int $profile)
    {
        $this->access->role($profile, $request->user()->id);
        return DB::table('care_executions as execution')
            ->join('care_assignments as assignment', 'assignment.id', '=', 'execution.care_assignment_id')
            ->join('users', 'users.id', '=', 'execution.recorded_by')
            ->where('assignment.care_profile_id', $profile)->orderByDesc('execution.day')
            ->orderByDesc('execution.recorded_at')->limit(100)
            ->get(['execution.*', 'users.name as recorder', 'assignment.template'])
            ->map(function ($entry) {
                $entry->title = $this->templates->title($entry->template);
                $entry->answers = json_decode($entry->answers, true, flags: JSON_THROW_ON_ERROR);
                $entry->opened_steps = json_decode($entry->opened_steps, true, flags: JSON_THROW_ON_ERROR);
                foreach (['performed_at', 'recorded_at'] as $field) {
                    $entry->$field = $entry->$field ? CarbonImmutable::parse($entry->$field, 'UTC')->toIso8601String() : null;
                }
                return $entry;
            });
    }
}
