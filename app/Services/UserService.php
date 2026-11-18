<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserService
{
    public function __construct(private readonly FileService $fileService) {}

    public function create(array $data, ?UploadedFile $photo = null): User
    {
        $role = $data['role'] ?? null;
        unset($data['role'], $data['photo'], $data['password_confirmation']);

        $user = DB::transaction(function () use ($data, $role) {
            $user = User::create($data);

            if ($role) {
                $user->assignRole($role);
            }

            return $user;
        });

        if ($photo) {
            try {
                $this->fileService->upload($user, $photo, 'avatar');
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $user;
    }
}
