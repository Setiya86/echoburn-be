<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['message' => 'Register success', 'user' => $user]);
    }

    // LOGIN
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        return $this->respondWithToken($token);
    }

    // USER DETAIL
    public function me()
    {
        return response()->json(auth()->user());
    }

    // REFRESH TOKEN
    public function refresh()
    {
        try {
            // Coba refresh token
            // Jika token masih dalam masa tenggang (8 jam), ini akan berhasil
            $newToken = auth()->refresh();
            
            return $this->respondWithToken($newToken);

        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            // Ini terjadi jika token sudah LEWAT batas refresh (misal > 8 jam)
            return response()->json(['error' => 'Refresh token expired. Silakan login ulang.'], 401);
            
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            // Ini terjadi jika token sudah pernah dipakai refresh sebelumnya (Double usage)
            return response()->json(['error' => 'Token has been invalidated.'], 401);

        } catch (\Exception $e) {
            // Error lain (token tidak valid formatnya, dll)
            return response()->json(['error' => 'Token invalid'], 401);
        }
    }

    // LOGOUT
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logout success']);
    }

    // Format token response
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
