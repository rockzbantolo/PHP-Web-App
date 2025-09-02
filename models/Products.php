<?php
namespace Mws\Models;

use Mws\Core\Database;
use Exception;

class Products {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function findProductById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE ProductID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

}