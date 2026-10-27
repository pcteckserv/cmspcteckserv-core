<?php

namespace Pcteckserv\CmsCore\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Pcteckserv\CmsCore\Models\UserProfile;
use RuntimeException;
use Throwable;

class StaffProfileUpdater
{
    public static function rules(): array
    {
        return [
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'remove_avatar' => 'nullable|boolean',
            'biography' => 'nullable|string|max:2000',
            'languages_present' => 'nullable|boolean',
            'languages' => 'nullable|array|max:20',
            'languages.*' => 'required|string|max:80|distinct',
            'extra_languages' => 'nullable|string|max:1000',
        ];
    }

    public function update(Request $request, int|string $userId): void
    {
        $profile = UserProfile::firstOrNew(['user_id' => $userId]);
        if ($request->boolean('languages_present')) {
            $languages = collect(array_merge($request->input('languages', []) ?: [], preg_split('/[\r\n;,]+/u', (string) $request->input('extra_languages', ''))))->map(fn ($language) => trim($language))->filter()->unique()->values()->all();
            Validator::make(['languages' => $languages], ['languages' => 'array|max:20', 'languages.*' => 'string|max:80'])->validate();
            $profile->languages = $languages;
        }
        $oldAvatar = $profile->avatar_path;
        $newAvatar = $request->file('avatar')?->store('staff-avatars', 'public');
        if ($request->hasFile('avatar') && ! $newAvatar) {
            throw new RuntimeException('Não foi possível guardar a fotografia de perfil.');
        }
        if ($newAvatar || $request->boolean('remove_avatar')) {
            $profile->avatar_path = $newAvatar ?: null;
        }
        if ($request->has('biography')) {
            $profile->biography = $request->input('biography');
        }
        try {
            $profile->save();
        } catch (Throwable $e) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $e;
        }
        if ($oldAvatar && $oldAvatar !== $profile->avatar_path && str_starts_with($oldAvatar, 'staff-avatars/')) {
            DB::afterCommit(fn () => Storage::disk('public')->delete($oldAvatar));
        }
    }
}
