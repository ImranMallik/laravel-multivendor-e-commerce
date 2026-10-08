<?php

namespace App\Actions\User;

use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class UpdateProfileAction
{
    public const AVATAR_FOLDER = 'users/avatars';

    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * Updates name, email and phone, and optionally replaces the avatar.
     * `avatar` is not mass assignable: it is only ever set here from a validated upload.
     *
     * @param  array{name: string, email: string, phone?: string|null}  $data
     */
    public function execute(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        $oldAvatar = $user->avatar;

        $user->fill($data);

        if ($avatar) {
            $user->avatar = $this->images->upload($avatar, self::AVATAR_FOLDER);
        }

        $user->save();

        // Delete the previous file only after the new one is stored and saved.
        if ($avatar) {
            $this->images->delete($oldAvatar);
        }

        return $user;
    }
}
