<?php
//require_once 'models/Users.php';
//require_once 'helpers/Jsonresp.php';
namespace Mws\Controllers;

use Mws\Models\Users;
use Mws\Helpers\Response;
use Throwable;

class UserController
{
    private Users $userModel;

    public function __construct()
    {
        $this->userModel = new Users();
    }

    public static function initRoutes($router)
    {
        // Authentication
        $router->register('POST', '/Login', [self::class, 'login']);

        // User Management
        $router->register('GET', '/Users', [self::class, 'getAllUsers']);
        $router->register('GET', '/Users/{id}', [self::class, 'getUserById']);
        $router->register('POST', '/Users', [self::class, 'createUser']);
        $router->register('PUT', '/Users', [self::class, 'updateUsers']);
        $router->register('DELETE', '/Users', [self::class, 'deleteUsers']);
    }

    public function login($data)
    {
        try {
            $username = $data['username'] ?? null;
            $password = $data['password'] ?? null;

            if (empty($username) || empty($password)) {
                return Response::unauthorized("Invalid credentials");
            }

            $user = $this->userModel->getByUsername($username);

            if (!$user) {
                return Response::notFound("User not found");
            }

            // Verify password
            if (!password_verify($password, $user['PasswordHash'])) {
                return Response::unauthorized("Incorrect password");
            }

            // Prepare response payload with new fields
            $response = [
                'UserID' => $user['UserID'],
                'RoleID' => $user['RoleID'],
                'FullName' => $user['FullName'],
                'Username' => $user['Username'],
                'Email' => $user['Email']
            ];

            return Response::success($response, "Welcome {$user['Username']}");

        } catch (Throwable $e) {
            return Response::error("Login failed", 500, $e->getMessage());
        }
    }

    public function getAllUsers($params)
    {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;

            $users = $this->userModel->getAllUsers($page, $limit);

            return Response::success($users, "Users retrieved successfully");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch users", 500, $e->getMessage());
        }
    }

    public function getUserById($id)
    {
        try {
            $user = $this->userModel->getById($id);
            return $user
                ? Response::success($user, "User found")
                : Response::notFound("User with ID $id not found");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch user", 500, $e->getMessage());
        }
    }

    public function createUser($data)
    {
        try {
 
            // Check if username already exists
            $existingUser = $this->userModel->getByUsername($data['username']);
            if ($existingUser) {
                return Response::error("Username already taken", 409); // 409 Conflict
            }

            // Hash password securely
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);

            $result = $this->userModel->create($data);

            return $result['success']
                ? Response::success(['id' => $result['id']], "User created successfully")
                : Response::error("User creation failed", 500, $result['error']);

        } catch (Throwable $e) {
            return Response::error("Failed to create user", 500, $e->getMessage());
        }
    }

    public function updateUsers($data) {
        try {

            if (isset($data['id'])) {
                // Handle single update
                $result = $this->userModel->update($data['id'], $data);
                return Response::success($result, "User updated successfully");
            }

            if (is_array($data)) {
                // Handle batch update
                $results = [];
                foreach ($data as $item) {
                    $results[] = $this->userModel->update($item['id'], $item);
                }
                return Response::success($results, "Batch Update complete");
            }

            return Response::error("Invalid update data", 400);
        } catch (Throwable $e) {
            return Response::error("Update failed", 500, $e->getMessage());
        }
    }

    public function deleteUsers($data)
    {
        try {
            // Single delete
            if (isset($data['id'])) {
                $result = $this->userModel->delete($data['id']);
                return Response::success($result, "User deleted successfully");
            }

            // Batch delete
            if (isset($data[0]['id'])) {
                $results = [];
                foreach ($data as $item) {
                    if (isset($item['id'])) {
                        $results[] = $this->userModel->delete($item['id']);
                    }
                }
                return Response::success($results, "Batch delete complete");
            }

            return Response::error("Invalid delete data", 400);
        } catch (Throwable $e) {
            return Response::error("Delete failed", 500, $e->getMessage());
        }
    }

}
