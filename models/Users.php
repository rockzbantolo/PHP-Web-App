<?php
//require_once 'core/Database.php';
namespace Mws\Models;

use Mws\Core\Database;
use Exception;


class Users {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /** Get all users */
    public function getAllUsers($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $stmt = $this->conn->prepare("
            SELECT 
                u.UserID,
                ur.RoleID,
                r.RoleName as Role,
                u.Username,
                CONCAT(up.FirstName, ' ', up.LastName) AS FullName,
                u.Email,
                u.CreatedAt
            FROM users u
            LEFT JOIN userroles ur ON u.UserID = ur.UserID
            LEFT JOIN userprofiles up ON u.UserID = up.UserID
            LEFT JOIN roles r ON r.RoleID = ur.RoleID
            ORDER BY u.UserID DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    }

    /** Get user by ID */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Get user by username */
    public function getByUsername($username) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.UserID,
                ur.RoleID,
                u.Username,
                u.PasswordHash,
                CONCAT(up.FirstName, ' ', up.LastName) AS FullName,
                u.Email
            FROM users u
            LEFT JOIN userroles ur ON u.UserID = ur.UserID
            LEFT JOIN userprofiles up ON u.UserID = up.UserID
            WHERE u.Username = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Create new user */
    public function create($data) {
        try {
            if (!isset($data['username'], $data['password_hash'], $data['email'])) {
                throw new Exception("Required fields missing");
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO users (Username, PasswordHash, Email, CreatedAt)
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->bind_param("sss", $data['username'], $data['password_hash'], $data['email']);
            $stmt->execute();
            $insertId = $stmt->insert_id;

            return ['success' => true, 'id' => $insertId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Update user info */
    public function update($id, $data)
    {
        if (empty($data)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }

        $fields = [];
        $values = [];
        $types  = '';

        // Dynamically add fields
        if (isset($data['username'])) {
            $fields[] = "Username = ?";
            $values[] = $data['username'];
            $types .= 's';
        }

        if (isset($data['password'])) {
            $fields[] = "PasswordHash = ?";
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
            $types .= 's';
        }

        /*
        if (isset($data['secretpass'])) {
            $fields[] = "SecretPass = ?";
            $values[] = $data['secretpass'];
            $types .= 's';
        }
        */

        // Add updated timestamp
        $fields[] = "UpdatedAt = NOW()";

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE UserID = ?";
        $stmt = $this->conn->prepare($sql);

        // Add ID to bind parameters
        $values[] = $id;
        $types .= 'i';

        $stmt->bind_param($types, ...$values);

        try {
            $stmt->execute();
            return ['success' => $stmt->affected_rows > 0];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Delete user */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return ['success' => $stmt->affected_rows > 0];
    }
}
