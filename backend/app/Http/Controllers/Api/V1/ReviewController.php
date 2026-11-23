<?php

namespace App\Http\Controllers\Api\V1;

use App\Care\CareAccess;
use App\Care\Templates;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReviewController
{
    public function __construct(private CareAccess $access, private Templates $templates) {}

    public function index(Request $request, int $profile): array
    {
        $this->access->requireProfessional($profile, $request->user()->id);
        $query = DB::table('care_executions as execution')
            ->join('care_assignments as assignment', 'assignment.id', '=', 'execution.care_assignment_id')
            ->leftJoin('care_reviews as review', 'review.care_execution_id', '=', 'execution.id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'review.reviewed_by')
            ->where('assignment.care_profile_id', $profile);
        $alerts = (clone $query)->join('care_alerts as alert', 'alert.care_execution_id', '=', 'execution.id')
            ->orderByDesc('alert.id')->limit(100)
            ->get(['alert.id', 'alert.source', 'alert.care_execution_id as execution_id', 'review.outcome']);
        $reviews = $query->whereNotNull('review.id')->get([
            'execution.id as execution_id', 'review.outcome', 'review.note', 'reviewer.name as reviewer', 'review.reviewed_at',
        ])->map(function ($review) {
            $review->reviewed_at = CarbonImmutable::parse($review->reviewed_at, 'UTC')->toIso8601String();
            return $review;
        });
        return ['alerts' => $alerts, 'reviews' => $reviews];
    }

    public function review(Request $request, int $profile, int $execution)
    {
        $this->access->requireProfessional($profile, $request->user()->id);
        abort_unless(DB::table('care_executions as execution')
            ->join('care_assignments as assignment', 'assignment.id', '=', 'execution.care_assignment_id')
            ->where('assignment.care_profile_id', $profile)->where('execution.id', $execution)->exists(), 404);
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['seen', 'followup'])],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        $saved = DB::table('care_reviews')->insertOrIgnore($data + [
            'care_execution_id' => $execution, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
        ]);
        abort_unless($saved, 409, 'Deze registratie is al bekeken.');
        return response()->json(['saved' => true], 201);
    }

    public function skills(Request $request, int $profile)
    {
        $user = $request->user()->id;
        $role = $this->access->role($profile, $user);
        $members = DB::table('care_members as member')->join('users', 'users.id', '=', 'member.user_id')
            ->where('member.care_profile_id', $profile)->where('member.role', 'caregiver');
        if ($role !== 'professional') {
            $members->where('member.user_id', $user);
        }
        $latest = DB::table('care_skills')->where('care_profile_id', $profile)
            ->where('template', Templates::NIGHT_BAG)->groupBy('user_id')->selectRaw('MAX(id) as id, user_id');

        return $members->leftJoinSub($latest, 'latest', 'latest.user_id', '=', 'member.user_id')
            ->leftJoin('care_skills as skill', 'skill.id', '=', 'latest.id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'skill.reviewed_by')
            ->orderBy('users.name')->get([
                'users.id', 'users.name', 'skill.status', 'skill.note', 'skill.method', 'skill.version',
                'reviewer.name as reviewer', 'skill.reviewed_at',
            ])->map(function ($skill) {
                $skill->reviewed_at = $skill->reviewed_at ? CarbonImmutable::parse($skill->reviewed_at, 'UTC')->toIso8601String() : null;
                return $skill;
            });
    }

    public function assess(Request $request, int $profile)
    {
        $this->access->requireProfessional($profile, $request->user()->id);
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('care_members', 'user_id')->where(
                fn ($query) => $query->where('care_profile_id', $profile)->where('role', 'caregiver')
            )],
            'status' => ['required', Rule::in(['demonstrated', 'practice', 'reassess'])],
            'method' => ['required', Rule::in(['demo_observation', 'demo_video'])],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        DB::table('care_skills')->insert($data + [
            'care_profile_id' => $profile, 'reviewed_by' => $request->user()->id, 'template' => Templates::NIGHT_BAG,
            'version' => $this->templates->instruction(Templates::NIGHT_BAG)['version'], 'reviewed_at' => now(),
        ]);
        return response()->json(['saved' => true], 201);
    }
}
