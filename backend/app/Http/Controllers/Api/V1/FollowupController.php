<?php

namespace App\Http\Controllers\Api\V1;

use App\Care\CareAccess;
use App\Care\Followups;
use App\Care\Templates;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FollowupController
{
    public function __construct(private CareAccess $access, private Followups $followups, private Templates $templates) {}

    private function query(int $profile): Builder
    {
        return DB::table('care_followups as check')
            ->join('care_executions as execution', 'execution.id', '=', 'check.care_execution_id')
            ->join('care_assignments as assignment', 'assignment.id', '=', 'execution.care_assignment_id')
            ->leftJoin('users as observer', 'observer.id', '=', 'check.answered_by')
            ->where('assignment.care_profile_id', $profile)
            ->select(['check.*', 'execution.recorded_by', 'execution.day', 'assignment.template', 'observer.name as observer']);
    }

    public function index(Request $request, int $profile)
    {
        $user = $request->user()->id;
        $role = $this->access->role($profile, $user);
        $now = CarbonImmutable::now();
        return $this->query($profile)->orderByDesc('check.due_at')->limit(100)->get()->map(
            function ($check) use ($role, $user, $now) {
                $due = CarbonImmutable::parse($check->due_at, 'UTC');
                return [
                    'id' => $check->id, 'execution_id' => $check->care_execution_id,
                    'title' => $this->templates->title($check->template), 'day' => $check->day,
                    'due_at' => $due->toIso8601String(), 'is_due' => $due <= $now,
                    'question' => Followups::QUESTIONS[$check->question], 'options' => Followups::ANSWERS,
                    'answer' => $check->answer, 'observer' => $check->observer,
                    'deviation' => $check->answer === $check->trigger,
                    'can_answer' => $check->answer === null && $due <= $now && $this->followups->mayAnswer($check, $role, $user),
                ];
            }
        );
    }

    public function answer(Request $request, int $profile, int $check)
    {
        $user = $request->user()->id;
        $role = $this->access->role($profile, $user);
        $record = $this->query($profile)->where('check.id', $check)->first();
        abort_unless($record, 404);
        abort_unless($this->followups->mayAnswer($record, $role, $user), 403);
        $now = CarbonImmutable::now();
        abort_if(CarbonImmutable::parse($record->due_at, 'UTC') > $now, 409, 'Deze nazorgcheck is gepland voor later.');
        $answer = $request->validate(['answer' => ['required', Rule::in(array_keys(Followups::ANSWERS))]])['answer'];

        return DB::transaction(function () use ($check, $record, $answer, $user, $now) {
            $saved = DB::table('care_followups')->where('id', $check)->whereNull('answered_at')
                ->update(['answer' => $answer, 'answered_by' => $user, 'answered_at' => $now]);
            abort_unless($saved, 409, 'De nazorgcheck is al beantwoord.');
            $deviation = $answer === $record->trigger;
            if ($deviation) {
                $this->followups->flag($record->care_execution_id, 'followup', $now);
            }
            return ['deviation' => $deviation, 'simulated' => true];
        });
    }
}
