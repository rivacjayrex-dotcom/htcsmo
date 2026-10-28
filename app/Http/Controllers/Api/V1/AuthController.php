<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $user = User::query()->where(function ($query) use ($validated) {
                $query->where('username', $validated['login'])->orWhere('school_id', $validated['login']);
            })->lockForUpdate()->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages(['login' => trans('auth.failed')]);
            }

            abort_unless($user->hasActiveAccess(), 403, 'Your account does not have active access.');

            $abilities = ['supplies:read', 'requisitions:read', 'requisitions:write'];
            if ($user->role === 'smo') {
                $abilities[] = 'supplies:write';
            }

            $expiresAt = now()->addMinutes(config('sanctum.expiration'));
            $token = $user->createToken($validated['device_name'], $abilities, $expiresAt);

            UserActivityLog::create([
                'user_id' => $user->id,
                'action' => 'API token issued',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return response()->json([
                'token_type' => 'Bearer',
                'access_token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toISOString(),
                'abilities' => $abilities,
                'user' => new UserResource($user),
            ], 201);
        });
    }

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
