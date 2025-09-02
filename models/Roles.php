<?php
namespace Mws\Models;

use Mws\Core\Database;
use Exception;

class Roles {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function findAllRoles() {
        $stmt = $this->conn->prepare("SELECT * FROM roles");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function findRoleById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM roles WHERE RoleID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createRole($data) {
        try {
            if (!isset($data['RoleName'], $data['Descriptions'])) {
                throw new Exception("Required fields missing");
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO roles (RoleName, Description)
                    VALUES (?, ?)"
            );
            
            $stmt->bind_param("sss", $data['RoleName'], $data['Descriptions']);
            $stmt->execute();
            $insertId = $stmt->insert_id;

            return ['success' => true, 'id' => $insertId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}