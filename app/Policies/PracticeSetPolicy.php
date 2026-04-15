<?php

namespace App\Policies;

use App\Models\PracticeSet;
use App\Models\User;

class PracticeSetPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, PracticeSet $practiceSet): bool
    {
        return $user->id === $practiceSet->user_id;
    }

    public function update(User $user, PracticeSet $practiceSet): bool
    {
        return $user->id === $practiceSet->user_id;
    }

    public function delete(User $user, PracticeSet $practiceSet): bool
    {
        return $user->id === $practiceSet->user_id;
    }
}
