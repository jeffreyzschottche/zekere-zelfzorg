<?php

namespace App\Care;

use Illuminate\Support\Facades\DB;

class CareAccess
{
    public function role(int $profile, int $user): string
    {
        $role = DB::table('care_members')->where('care_profile_id', $profile)
            ->where('user_id', $user)->value('role');
        abort_if($role === null, 404);
        return $role;
    }

    public function requireProfessional(int $profile, int $user): void
    {
        abort_unless($this->role($profile, $user) === 'professional', 403);
    }
}
