<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\User;

class SharePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Share $share): bool
    {
        return $share->shared_by === $user->id || $share->shared_with === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Share $share): bool
    {
        return $share->shared_by === $user->id;
    }

    public function delete(User $user, Share $share): bool
    {
        return $share->shared_by === $user->id;
    }
}
