<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $admin = $request->user();

        return response()->json([
            'id'            => $admin->id,
            'name'          => $admin->name,
            'email'         => $admin->email,
            'phone'         => $admin->phone,
            'position'      => $admin->position,
            'profile_photo' => $admin->profile_photo
                ? $this->fullPhotoUrl(Storage::url($admin->profile_photo))
                : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique((new User())->getTable(), 'email')->ignore($admin->id),
            ],
            'phone'    => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $admin->fill($validated)->save();

        return response()->json([
            'id'            => $admin->id,
            'name'          => $admin->name,
            'email'         => $admin->email,
            'phone'         => $admin->phone,
            'position'      => $admin->position,
            'profile_photo' => $admin->profile_photo
                ? $this->fullPhotoUrl(Storage::url($admin->profile_photo))
                : null,
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($admin->profile_photo && Storage::disk('public')->exists($admin->profile_photo)) {
            Storage::disk('public')->delete($admin->profile_photo);
        }

        $path = $validated['photo']->store('admin_photos', 'public');

        $admin->profile_photo = $path;
        $admin->save();

        $publicUrl = $this->fullPhotoUrl(Storage::url($path));

        return response()->json([
            'profile_photo' => $publicUrl,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        if (!Hash::check($validated['current_password'], $admin->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        $admin->password = Hash::make($validated['password']);
        $admin->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    private function fullPhotoUrl(string $maybeRelative): string
    {
        return url($maybeRelative);
    }
}

