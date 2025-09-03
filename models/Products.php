<?php
namespace Mws\Models;

use Mws\Core\Connection;
use Exception;

class Products {

    public function findProductById($id) {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare("SELECT * FROM products WHERE ProductID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $store = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);
        return $store;
    }

}