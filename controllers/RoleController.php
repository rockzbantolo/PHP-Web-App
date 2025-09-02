<?php
namespace Mws\Controllers;

use Mws\Models\Roles;
use Mws\Helpers\Response;
use Throwable;

class RoleController
{
    private Roles $roleModel;

    public function __construct() {
        $this->roleModel = new Roles();
    }

    public static function initRoutes($router)
    {
        $router->register('GET', '/Role', [self::class, 'getAllRoles']);
        $router->register('GET', '/Role/{id}', [self::class, 'getRoleById']);
        $router->register('POST', '/Role', [self::class, 'saveRoles']);
        $router->register('PUT', '/Role', [self::class, 'updateRoles']);
        $router->register('DELETE', '/Role', [self::class, 'deleteRoles']);
    }

    public function getRoleById($id) {
        try {
            $user = $this->roleModel->findRoleById($id);
            return $user
                ? Response::success($user, "Role found")
                : Response::notFound("Role with ID $id not found");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch Role", 500, $e->getMessage());
        }
    }

    public function saveRoles($data) {
        try {

            // Check if role already exists
            $existingUser = $this->roleModel->findRoleById($data['rolename']);
            if ($existingUser) {
                return Response::error("Role already created", 409); // 409 Conflict
            }

            // Hash password securely
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);

            $result = $this->roleModel->createRole($data);

            return $result['success']
                ? Response::success(['id' => $result['id']], "Role created successfully")
                : Response::error("Role creation failed", 500, $result['error']);

        } catch (Throwable $e) {
            return Response::error("Failed to create Role", 500, $e->getMessage());
        }
    }

}