<?php
namespace Mws\Models;

use Mws\Core\Connection;
use Exception;


class Users {

    // ----------------------
    // FIND ALL USERS (with pagination)
    // ----------------------
    public function findAllUsers($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $conn = Connection::getInstance();

        $stmt = $conn->prepare("
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

        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);

        // Reuse pagination helpers
        $pagination = $this->getPaginationInfo($page, $limit);

        return [
            'data'       => $users,
            'pagination' => $pagination
        ];
    }


    public function findUserById($id) {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        Connection::releaseConnection($conn);
        return $result;
    }

    public function findByUsername($username) {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("
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
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        Connection::releaseConnection($conn);
        return $result;
    }

    public function createUser($data) {

        $conn = Connection::getInstance();
        if (!isset($data['username'], $data['password_hash'], $data['email'])) {
            throw new Exception("Required fields missing");
        }

        $stmt = $conn->prepare("
            INSERT INTO users (Username, PasswordHash, Email)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param(
            "sss",
            $data['username'],
            $data['password_hash'],   // already hashed before calling
            $data['email']
        );

        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        Connection::releaseConnection($conn);
        return ['success' => true, 'id' => $insertId];
    }


    public function updateUser($id, $data) {

        $conn = Connection::getInstance();
        $sql = "UPDATE users 
                SET 
                    Username      = ?, 
                    PasswordHash  = ?, 
                    Email         = ?, 
                    SecretPass    = ?, 
                    isTwoFactor   = ?, 
                    LastLogin     = ?, 
                    LastLogout    = ?, 
                    FailedAttemps = ?, 
                    LockUntil     = ?, 
                    isOnline      = ?, 
                    IsActive      = ?, 
                    UpdatedAt     = NOW()
                WHERE UserID = ?";

        $stmt = $conn->prepare($sql);

        // Fixed: 12 characters for 12 parameters (11 data + 1 id)
        // s=string, i=integer, s=string (for nullable datetime)
        $stmt->bind_param(
            "ssssississii", // 12 characters total
            $data['Username'],        // Use exact case from JSON
            $data['PasswordHash'],    
            $data['Email'],           
            $data['SecretPass'],      
            $data['isTwoFactor'],     // integer
            $data['LastLogin'],       // string (datetime)
            $data['LastLogout'],      // string (datetime)  
            $data['FailedAttemps'],   // integer
            $data['LockUntil'],       // string (nullable datetime)
            $data['isOnline'],        // integer
            $data['IsActive'],        // integer
            $id                       // integer - WHERE parameter
        );

        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        Connection::releaseConnection($conn);
        return $success;
 
    }

    public function deleteUser($id) {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("DELETE FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        Connection::releaseConnection($conn);
        return ['success' => $success];
    }

    // ----------------------
    // GET TOTAL COUNT
    // ----------------------
    public function getTotalCount() {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);
        return (int) $row['total'];
    }

    // ----------------------
    // GET TOTAL PAGES
    // ----------------------
    public function getTotalPages($limit = 10) {
        $totalRecords = $this->getTotalCount();
        return ceil($totalRecords / $limit);
    }

    // ----------------------
    // PAGINATION INFO
    // ----------------------
    public function getPaginationInfo($currentPage, $limit) {
        $totalRecords = $this->getTotalCount();
        $totalPages   = ceil($totalRecords / $limit);

        return [
            'current_page'  => $currentPage,
            'per_page'      => $limit,
            'total_records' => $totalRecords,
            'total_pages'   => $totalPages,
            'has_previous'  => $currentPage > 1,
            'has_next'      => $currentPage < $totalPages,
            'from'          => (($currentPage - 1) * $limit) + 1,
            'to'            => min($currentPage * $limit, $totalRecords)
        ];
    }

}
