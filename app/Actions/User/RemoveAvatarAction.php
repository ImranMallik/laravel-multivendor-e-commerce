<?php

namespace App\Actions\User;

use App\Models\User;
use App\Services\ImageUploadService;

class RemoveAvatarAction
{
    public function __construct(private readonly ImageUploadService $images) {}

    public function execute(User $user): void
    {
        $oldAvatar = $user->avatar;

        $user->avatar = null;
        $user->save();

        $this->images->delete($oldAvatar);
    }
}
