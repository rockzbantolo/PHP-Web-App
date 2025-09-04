<?php

namespace Mws\Controllers;

use Mws\Models\Users;
use Mws\Helpers\Response;
use Mws\Core\StatusCode;
use Throwable;

class UserController 
{
    private Users $userModel;

    public function __construct() {
        $this->userModel = new Users();
    }

    public function getAllUsers() {
        try {

            $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

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
