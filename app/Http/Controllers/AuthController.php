<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'lastname'     => 'nullable|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:6|confirmed',
            'usuario_tipo' => 'nullable|integer|in:1,2,3',
        ]);

        $user = new User([
            'name'     => $request->name,
            'lastname' => $request->input('lastname') ?? '',
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'usuario_tipo' => $request->input('usuario_tipo') ?: 3,
            'perfil' => 1,
            'pass' => '',
            'user_name' => '',
        ]);
        $user->save();

        $tokenResult = $user->createToken('PadelMatch Mobile');
        $token = $tokenResult->token;
        $token->save();

        return response()->json($this->mobileTokenResponse($user, $tokenResult), 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|string|email',
            'password'    => 'required|string',
            'remember_me' => 'boolean',
        ]);

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('PadelMatch Mobile');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
        $token->save();

        return response()->json($this->mobileTokenResponse($user, $tokenResult));
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 
            'Successfully logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    private function mobileTokenResponse(User $user, $tokenResult): array
    {
        $expiresAt = $tokenResult->token->expires_at
            ? Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
            : null;

        return [
            'token' => $tokenResult->accessToken,
            'userId' => (string) $user->id,
            'displayName' => trim($user->name . ' ' . $user->lastname),
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
        ];
    }
}