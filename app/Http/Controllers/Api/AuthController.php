<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\Users\Application\Services\UserService;
use Core\Users\Ports\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function register(Request $request): JsonResponse
    {
        try {
            $userData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = $this->userService->createUser($userData);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->getId()->getValue(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()->getValue(),
                ]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['general' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $result = $this->userService->authenticate(
                $credentials['email'],
                $credentials['password']
            );

            if (!$result) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $result['user']->getId()->getValue(),
                    'name' => $result['user']->getName(),
                    'email' => $result['user']->getEmail()->getValue(),
                ],
                'token' => $result['token']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}