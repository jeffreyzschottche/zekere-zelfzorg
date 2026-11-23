<?php

namespace App\Http\Controllers\Api\V1;

use App\Care\CareAccess;
use App\Care\Planning;
use App\Care\Templates;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CareController
{
    public function profiles(Request $request)
    {
        return DB::table('care_members')->where('user_id', $request->user()->id)
            ->join('care_profiles', 'care_profiles.id', '=', 'care_members.care_profile_id')
            ->orderBy('care_profiles.name')->get(['care_profiles.id', 'care_profiles.name', 'role']);
    }

    public function today(Request $request, int $profile, CareAccess $access, Planning $planning, Templates $templates)
    {
        $user = $request->user()->id;
        $role = $access->role($profile, $user);
        $day = CarbonImmutable::now(Planning::ZONE)->toDateString();
        $members = DB::table('care_members')->where('care_profile_id', $profile)
            ->join('users', 'users.id', '=', 'care_members.user_id')
            ->orderBy('users.name')->get(['users.id', 'users.name', 'role']);
        $tasks = DB::table('care_assignments')->where('care_profile_id', $profile)
            ->orderBy('time')->get()->map(function ($assignment) use ($planning, $templates, $day, $user, $role) {
                $instant = $planning->scheduledAt($assignment, $day);
                if (!$instant) {
                    return null;
                }
                return [
                    'id' => $assignment->id, 'title' => $templates->title($assignment->template),
                    'assignee_id' => $assignment->assignee_id, 'day' => $day,
                    'time' => $instant->setTimezone(Planning::ZONE)->format('H:i'),
                    'can_execute' => $role !== 'professional' && $user === $assignment->assignee_id,
                ];
            })->filter()->values();

        return ['day' => $day, 'members' => $members, 'tasks' => $tasks];
    }

    public function assign(Request $request, int $profile, CareAccess $access)
    {
        $access->requireProfessional($profile, $request->user()->id);
        $data = $request->validate([
            'assignee_id' => ['required', 'integer', Rule::exists('care_members', 'user_id')->where(
                fn ($query) => $query->where('care_profile_id', $profile)->whereIn('role', ['patient', 'caregiver'])
            )],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'time' => ['required', 'date_format:H:i'],
            'repeat' => ['required', Rule::in(['once', 'daily', 'alternate', 'weekly'])],
        ]);
        $id = DB::table('care_assignments')->insertGetId($data + [
            'care_profile_id' => $profile, 'template' => Templates::NIGHT_BAG,
        ]);
        return response()->json(['id' => $id], 201);
    }
}
