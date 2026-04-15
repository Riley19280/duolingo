<?php

namespace App\Policies;

use App\Models\PracticeSession;
use App\Models\User;

class PracticeSessionPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, PracticeSession $practiceSession): bool
    {
        return $user->id === $practiceSession->user_id;
    }

    public function complete(User $user, PracticeSession $practiceSession): bool
    {
        return $user->id === $practiceSession->user_id;
    }
}
