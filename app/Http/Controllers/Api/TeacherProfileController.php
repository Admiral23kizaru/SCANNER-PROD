<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $teacher_profile = $request->user();

        return response()->json([
            'id'            => $teacher_profile->id,
            'name'          => $teacher_profile->name,
            'email'         => $teacher_profile->email,
            'phone'         => $teacher_profile->phone,
            'position'      => $teacher_profile->position,
            'profile_photo' => $teacher_profile->profile_photo
                ? $this->fullPhotoUrl($teacher_profile->profile_photo)
                : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $teacher_profile = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique((new User())->getTable(), 'email')->ignore($teacher_profile->id),
            ],
            'phone'    => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $teacher_profile->fill($validated)->save();

        return response()->json([
            'id'            => $teacher_profile->id,
            'name'          => $teacher_profile->name,
            'email'         => $teacher_profile->email,
            'phone'         => $teacher_profile->phone,
            'position'      => $teacher_profile->position,
            'profile_photo' => $teacher_profile->profile_photo
                ? $this->fullPhotoUrl($teacher_profile->profile_photo)
                : null,
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $teacher_profile = $request->user();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $this->storePublicStorageImage(
            $validated['photo'],
            'teacher_photos',
            $teacher_profile->profile_photo
        );

        $teacher_profile->profile_photo = $path;
        $teacher_profile->save();

        $publicUrl = $this->fullPhotoUrl($path);

        return response()->json([
            'profile_photo' => $publicUrl,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $teacher_profile = $request->user();

        $validated = $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        if (!Hash::check($validated['current_password'], $teacher_profile->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        $teacher_profile->password = Hash::make($validated['password']);
        $teacher_profile->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    private function fullPhotoUrl(string $maybeRelative): string
    {
        $clean = ltrim(preg_replace('#^(public/|storage/|/storage/)#', '', $maybeRelative) ?? $maybeRelative, '/');
        return url('/storage/' . $clean);
    }

    /**
     * Store an uploaded image directly under public/storage/<dir> and return a relative path (<dir>/<filename>).
     */
    private function storePublicStorageImage(\Illuminate\Http\UploadedFile $file, string $dir, ?string $previousRelativePath = null): string
    {
        $base = public_path('storage' . DIRECTORY_SEPARATOR . $dir);
        if (!File::exists($base)) {
            File::makeDirectory($base, 0755, true);
        }

        if ($previousRelativePath) {
            $prevClean = ltrim(preg_replace('#^(public/|storage/|/storage/)#', '', $previousRelativePath) ?? $previousRelativePath, '/');
            $prevAbs = public_path('storage' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $prevClean));
            if (File::exists($prevAbs)) {
                @File::delete($prevAbs);
            }
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString() . '.' . $ext;
        $file->move($base, $filename);

        return $dir . '/' . $filename;
    }
}

