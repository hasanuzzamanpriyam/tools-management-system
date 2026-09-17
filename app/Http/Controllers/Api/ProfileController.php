<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);

        $request->user()->update($data);

        return response()->json($request->user());
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', File::image()->max(2048)],
        ]);

        $user = $request->user();
        $file = $request->file('avatar');
        $path = $file->storeAs('avatars', sprintf('%s.%s', $user->id, $file->extension()), 'local');

        if ($user->avatar_path && $user->avatar_path !== $path) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        return response()->json($user);
    }

    public function avatar(Request $request): BinaryFileResponse
    {
        $path = $request->user()->avatar_path;

        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'Content-Type' => Storage::disk('local')->mimeType($path),
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => null]);

        return response()->json($user);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $request->user()->update(['password' => $data['password']]);

        $request->user()->tokens()
            ->whereKeyNot($request->user()->currentAccessToken()->getKey())
            ->delete();

        return response()->json(['message' => 'Password updated']);
    }
}
