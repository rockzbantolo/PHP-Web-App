<?php
namespace Mws\Models;

use Mws\Core\Connection;
use Exception;

class Roles {

    public function findAllRoles() {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("SELECT * FROM stores");
        $stmt->execute();
        $result = $stmt->get_result();
        $store = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);
        return $store;
    }

    public function findRoleById($id) {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("SELECT * FROM roles WHERE RoleID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $store = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);
        return $store;
    }

    public function createRole($data) {

        $conn = Connection::getInstance();

        $stmt = $conn->prepare("
            INSERT INTO stores
            (StoreCode, StoreName, StoreAddress, Code, ContactPerson, ContactNum, Email, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sssssss",
            $data['StoreCode'],
            $data['StoreName'],
            $data['StoreAddress'],
            $data['Code'],
            $data['ContactPerson'],
            $data['ContactNum'],
            $data['Email']
        );

        $success = $stmt->execute();
        $stmt->close();

        Connection::releaseConnection($conn);

        return $success;
    }

}