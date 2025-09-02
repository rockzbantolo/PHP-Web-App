<?php
namespace Mws\Models;

use Mws\Core\Database;
use Exception;


class Store {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function findStoreById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM stores WHERE StoreID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

}