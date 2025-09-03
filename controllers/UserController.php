<?php

namespace Mws\Controllers;

use Mws\Models\Users;
use Mws\Helpers\Response;
use Mws\Core\StatusCode;
use Mws\Core\RouteControllerInterface;
use Throwable;

class UserController implements RouteControllerInterface
{
    private Users $userModel;

    public function __construct() {
        $this->userModel = new Users();
    }

    public static function initRoutes($router)
    {
        // Authentication
        $router->register('POST', '/Login', [self::class, 'login']);

        // User Management (single)
        $router->register('GET', '/Users', [self::class, 'getAllUsers']);
        $router->register('GET', '/Users/{id}', [self::class, 'getUserById']);
        $router->register('POST', '/Users', [self::class, 'saveUser']);
        $router->register('PUT', '/Users/{id}', [self::class, 'updateUserById']);
        $router->register('DELETE', '/Users/{id}', [self::class, 'deleteUserById']);

        // Batch operations
        $router->register('POST', '/Users/Batch', [self::class, 'batchCreateUsers']);
        $router->register('PUT', '/Users/Batch', [self::class, 'batchUpdateUsers']);
        $router->register('DELETE', '/Users/Batch', [self::class, 'batchDeleteUsers']);
    }

    /** USERS */
    public function login($data) {
        try {
            $username = $data['username'] ?? null;
            $password = $data['password'] ?? null;

            if (empty($username) || empty($password)) {
                return Response::unauthorized("Invalid credentials");
            }

            $user = $this->userModel->findByUsername($username);

            if (!$user) {
                return Response::notFound("User not found");
            }

            if (!password_verify($password, $user['PasswordHash'])) {
                return Response::unauthorized("Incorrect password");
            }

            $response = [
                'UserID'   => $user['UserID'],
                'RoleID'   => $user['RoleID'],
                'FullName' => $user['FullName'],
                'Username' => $user['Username'],
                'Email'    => $user['Email']
            ];

            return Response::success($response, "Welcome {$user['Username']}");

        } catch (Throwable $e) {
            return Response::error("Login failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getAllUsers($params) {
        try {
            $page  = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;

            $result = $this->userModel->findAllUsers($page, $limit);

            return Response::success(
                $result['data'],
                "Users retrieved successfully",
                StatusCode::OK,
                $result['pagination']
            );
        } catch (Throwable $e) {
            return Response::error("Failed to fetch users", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getUserById($id) {
        try {
            $user = $this->userModel->findUserById($id);
            return $user
                ? Response::success($user, "User found")
                : Response::notFound("User with ID $id not found");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch user", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function saveUser($data) {
        try {
            $existingUser = $this->userModel->findByUsername($data['username']);
            if ($existingUser) {
                return Response::error("Username already taken", StatusCode::CONFLICT);
            }

            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);

            $result = $this->userModel->createUser($data);

            return $result['success']
                ? Response::success(['id' => $result['id']], "User created successfully")
                : Response::error("User creation failed", StatusCode::INTERNAL_SERVER_ERROR, $result['error']);
        } catch (Throwable $e) {
            return Response::error("Failed to create user", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function updateUserById($id, $data) {
        try {
            $result = $this->userModel->updateUser($id, $data);
            return Response::success([], "User updated successfully");
        } catch (Throwable $e) {
            return Response::error("Update failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function deleteUserById($id) {
        try {
            $result = $this->userModel->deleteUser($id);
            return Response::success([], "User deleted successfully");
        } catch (Throwable $e) {
            return Response::error("Delete failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }


    // BATCH APPROACH
    public function batchCreateUsers($data) {
        try {
            if (!is_array($data) || empty($data)) {
                return Response::error("Invalid batch data", StatusCode::BAD_REQUEST);
            }

            $results = [];
            foreach ($data as $item) {
                // Check username duplication
                $existingUser = $this->userModel->findByUsername($item['username']);
                if ($existingUser) {
                    $results[] = ['success' => false, 'error' => "Username {$item['username']} already exists"];
                    continue;
                }

                // Hash password
                $item['password_hash'] = password_hash($item['password'], PASSWORD_BCRYPT);
                unset($item['password']);

                $results[] = $this->userModel->createUser($item);
            }

            return Response::success($results, "Batch create complete");
        } catch (Throwable $e) {
            return Response::error("Batch create failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function batchUpdateUsers($data) {
        try {
            $results = [];
            foreach ($data as $item) {
                if (isset($item['id'])) {
                    $results[] = $this->userModel->updateUser($item['id'], $item);
                }
            }
            return Response::success([], "Batch update complete");
        } catch (Throwable $e) {
            return Response::error("Batch update failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function batchDeleteUsers($data) {
        try {
            $results = [];
            foreach ($data as $item) {
                if (isset($item['id'])) {
                    $results[] = $this->userModel->deleteUser($item['id']);
                }
            }
            return Response::success([], "Batch delete complete");
        } catch (Throwable $e) {
            return Response::error("Batch delete failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
