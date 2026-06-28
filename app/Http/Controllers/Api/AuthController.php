<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login API untuk petugas
     * POST /api/login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                    'code' => 422
                ], 422);
            }

            $credentials = $request->only('email', 'password');
            
            // Cari user dengan role petugas (role_id = 3)
            $user = User::where('email', $credentials['email'])->where('role_id', 3)->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email atau password salah, atau Anda bukan petugas',
                    'code' => 401
                ], 401);
            }

            // Generate API token
            $token = $user->createToken('api-token', ['api'])->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role_id' => $user->role_id,
                        'instansi_id' => $user->instansi_id,
                    ],
                    'token' => $token
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    /**
     * Logout API
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                    'code' => 401
                ], 401);
            }

            // Hapus semua tokens
            $user->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logout berhasil'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    /**
     * Mendapatkan data user yang login
     * GET /api/me
     */
    public function getProfile(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile berhasil diambil',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'instansi_id' => $user->instansi_id,
                    'instansi' => $user->instansi ? [
                        'id' => $user->instansi->id_instansi,
                        'nama' => $user->instansi->nama_instansi,
                        'kode' => $user->instansi->kode_instansi,
                    ] : null,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }
}
