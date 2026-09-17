<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()->latest()->paginate()
        );
    }

    public function update(Request $request, User $user): JsonResponse|UserResource
    {
        $data = $request->validate([
            'role' => ['sometimes', 'required', Rule::in([
                User::ROLE_USER,
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN,
            ])],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        $targetRole = $data['role'] ?? $user->role;
        $targetActive = $data['is_active'] ?? $user->is_active;

        if ($user->is($request->user())
            && ($targetRole !== $user->role || $targetActive !== (bool) $user->is_active)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot change your own role or deactivate your own account.');
        }

        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Only super admins can modify super admin accounts.');
        }

        $user->update($data);

        (new AuditLogger)->record($request, 'user.updated', $user, [
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ]);

        return new UserResource($user);
    }
}
