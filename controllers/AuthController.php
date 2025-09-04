<?php
namespace Mws\Controllers;

use Mws\Models\Users;
use Mws\Helpers\Response;
use Mws\Core\StatusCode;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthController
{
    private Users $userModel;
    private string $jwtSecret;

    public function __construct() {
        $this->userModel = new Users();
        $this->jwtSecret = getenv('JWT_SECRET'); // load from .env
    }

    // ----------------------
    // LOGIN
    // ----------------------
    public function login($data)
    {
        try {
            $username = $data['username'] ?? null;
            $password = $data['password'] ?? null;

            if (empty($username) || empty($password)) {
                return Response::unauthorized("Invalid credentials");
            }

            $user = $this->userModel->findByUsername($username);

            if (!$user) {
                return Response::unauthorized("Invalid username or password");
            }

            // ---------------- LOCKOUT CHECK ----------------
            if (!empty($user['LockUntil']) && strtotime($user['LockUntil']) > time()) {
                return Response::unauthorized("Account locked. Try again later.");
            }

            // ---------------- PASSWORD CHECK ----------------
            if (!password_verify($password, $user['PasswordHash'])) {
                $failedAttempts = (int)$user['FailedAttemps'] + 1;

                // Default lock after 5 failed attempts
                if ($failedAttempts >= 5) {
                    $lockUntil = date("Y-m-d H:i:s", strtotime("+10 minutes")); // SET TIME LOCK
                    $this->userModel->updateLoginAttempts($user['UserID'], $failedAttempts, $lockUntil);
                    return Response::unauthorized("Account locked for 15 minutes due to too many failed attempts.");
                } else {
                    $this->userModel->updateLoginAttempts($user['UserID'], $failedAttempts, null);
                    return Response::unauthorized("Invalid username or password. Attempts left: " . (5 - $failedAttempts));
                }
            }

            // ---------------- SUCCESSFUL LOGIN ----------------
            $this->userModel->resetLoginAttempts($user['UserID']);

            // Issue JWT
            $issuedAt = time();
            $expire   = $issuedAt + (60 * 15); // 15 min expiry
            $payload = [
                'iss' => "mws-app",
                'sub' => $user['UserID'],
                'iat' => $issuedAt,
                'exp' => $expire
            ];

            $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');
            $refreshToken = bin2hex(random_bytes(32));
            $this->userModel->storeTokens($user['UserID'], $jwt, $refreshToken, $expire);
            
            // Format only for response
            // ---------------- TIME REMAINING ----------------
            $remainingSeconds = $expire - time();
            $minutes = floor($remainingSeconds / 60);
            $seconds = $remainingSeconds % 60;
            $expireRemaining = sprintf("%d:%02d", $minutes, $seconds);
            
            return Response::success([
                'access_token'  => $jwt,
                'refresh_token' => $refreshToken,
                'expires_in'    => $expireRemaining,
                'user'          => [
                    'userId'   => $user['UserID'],
                    'username' => $user['Username'],
                    'email'    => $user['Email'],
                    'fullName' => $user['FullName'] ?? ''
                ]
            ], "Welcome {$user['Username']}");

        } catch (Throwable $e) {
            return Response::error("Login failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ----------------------
    // REGISTER
    // ----------------------
    public function register($data)
    {
        try {
            // hash password before creating
            if (!isset($data['password']) || !isset($data['username']) || !isset($data['email'])) {
                return Response::notFound("Missing required fields");
            }

            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);

            $result = $this->userModel->createUser($data);

            return Response::success([
                'user_id' => $result['id']
            ], "Registered successfully", StatusCode::CREATED);

        } catch (Throwable $e) {
            return Response::error("Registration failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ----------------------
    // LOGOUT
    // ----------------------
    public function logout($data)
    {
        try {
            $userId = $data['user_id'] ?? null;

            if (!$userId) {
                return Response::notFound("Missing user_id");
            }

            $this->userModel->clearTokens($userId);

            return Response::success([], "User: {$userId} Logout successfully");

        } catch (Throwable $e) {
            return Response::error("Logout failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ----------------------
    // REFRESH TOKEN
    // ----------------------
    public function refreshToken($data)
    {
        try {
            $refreshToken = $data['refresh_token'] ?? null;
            if (!$refreshToken) {
                return Response::notFound("Missing refresh_token");
            }

            $user = $this->userModel->findByRefreshToken($refreshToken);
            if (!$user) {
                return Response::unauthorized("Invalid refresh token");
            }

            $issuedAt = time();
            $expire   = $issuedAt + (60 * 15);

            $payload = [
                'iss' => "mws-app",
                'sub' => $user['UserID'],
                'iat' => $issuedAt,
                'exp' => $expire
            ];

            $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');

            $this->userModel->updateAccessToken($user['UserID'], $jwt, $expire);

            return Response::success([
                'access_token' => $jwt,
                'expires_in'   => $expire
            ], "Token refreshed");

        } catch (Throwable $e) {
            return Response::error("Refresh token failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ----------------------
    // SESSION CHECK
    // ----------------------
    public function session($data)
    {
        try {
            $headers = getallheaders();
            $auth = $headers['Authorization'] ?? '';

            if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
                return Response::unauthorized("Missing or invalid token");
            }

            $jwt = $matches[1];

            $decoded = JWT::decode($jwt, new Key($this->jwtSecret, 'HS256'));

            return Response::success([
                'user_id' => $decoded->sub,
                'exp'     => $decoded->exp
            ], "Session valid");

        } catch (Throwable $e) {
            return Response::unauthorized("Session expired or invalid");
        }
    }
}
