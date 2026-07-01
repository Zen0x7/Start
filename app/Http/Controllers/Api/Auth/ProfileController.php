<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $devices = $user->totpDevices()->get()->map(fn ($d) => [
            'id' => $d->id,
            'label' => $d->label,
            'created_at' => $d->created_at,
            'last_used_at' => $d->last_used_at,
        ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'avatar_thumb' => $user->avatar_thumb,
            ],
            'totp_devices' => $devices,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'locale' => ['nullable', 'string', 'in:en,es'],
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        if ($emailChanged) {
            $existing = User::where('email', $validated['email'])->where('id', '!=', $user->id)->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => [__('validation.unique')],
                ]);
            }

        }

        $user->update($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
        }

        if ($emailChanged) {
            $token = app(JwtService::class)->buildEmailVerificationToken($user->email);
            $user->notify(new VerifyEmailNotification($token));
        }

        return response()->json([
            'message' => __('auth.profile_updated'),
            'email_changed' => $emailChanged,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'avatar_thumb' => $user->avatar_thumb,
            ],
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $path = $request->file('photo')->store('profile-photos', 'public');

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update(['profile_photo_path' => $path]);

        return response()->json([
            'message' => __('auth.photo_updated'),
            'avatar' => $user->avatar,
            'avatar_thumb' => $user->avatar_thumb,
        ]);
    }

    public function destroy(Request $request, TotpService $totp): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'totp_code' => ['required', 'string', 'size:6'],
        ]);

        $device = $totp->verifyAny($user, $validated['totp_code']);

        if ($device === null) {
            throw ValidationException::withMessages([
                'totp_code' => [__('totp.wrong_code')],
            ]);
        }

        $user->totpDevices()->delete();
        $user->totpCertificates()->delete();
        $user->delete();

        return response()->json(['message' => __('auth.account_deleted')]);
    }

    public function deleteDevice(Request $request, TotpService $totp): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'device_id' => ['required', 'integer', 'exists:totp_devices,id'],
            'totp_code' => ['required', 'string', 'size:6'],
        ]);

        if ($user->totpDevices()->count() <= 1) {
            throw HttpException::fromStatusCode(400, __('totp.last_device'));
        }

        $device = $user->totpDevices()->findOrFail($validated['device_id']);

        $verified = $totp->verifyAny($user, $validated['totp_code']);

        if ($verified === null) {
            throw ValidationException::withMessages([
                'totp_code' => [__('totp.wrong_code')],
            ]);
        }

        $device->delete();

        return response()->json(['message' => __('totp.device_removed')]);
    }
}
