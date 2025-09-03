<?php
namespace Mws\Models;

use Mws\Core\Connection;
use Exception;

class Stores {

    // ----------------------
    // FIND ALL STORES WITH PAGINATION
    // ----------------------
    public function findAllStores($page = 1, $limit = 10) {

        $offset = ($page - 1) * $limit;
        $conn = Connection::getInstance();

        $stmt = $conn->prepare("
            SELECT 
                s.StoreID,
                s.StoreCode,
                s.StoreName,
                s.StoreAddress,
                s.Code,
                s.ContactPerson,
                s.ContactNum,
                s.Email,
                st.StatusName AS Status,
                s.CreatedAt
            FROM stores s
            LEFT JOIN statuses st ON s.StatusID = st.StatusID
            ORDER BY s.StoreID DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $stores = [];
        while ($row = $result->fetch_assoc()) {
            $stores[] = $row;
        }
        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);

        // Get pagination metadata
        $pagination = $this->getPaginationInfo($page, $limit);

        return [
            'data' => $stores,
            'pagination' => $pagination
        ];
    }

    // ----------------------
    // FIND STORE BY ID
    // ----------------------
    public function findStoreById($id) {

        $conn = Connection::getInstance();

        $stmt = $conn->prepare("SELECT * FROM stores WHERE StoreID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $store = $result->fetch_assoc();

        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);

        return $store;
    }

    // ----------------------
    // FIND STORE BY NAME (LIKE)
    // ----------------------
    public function findByStoreName($name) {

        $conn = Connection::getInstance();
        $likeName = "%$name%";

        $stmt = $conn->prepare("SELECT * FROM stores WHERE StoreName LIKE ?");
        $stmt->bind_param("s", $likeName);
        $stmt->execute();

        $result = $stmt->get_result();
        $stores = [];
        while ($row = $result->fetch_assoc()) {
            $stores[] = $row;
        }

        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);

        return $stores;
    }

    // ----------------------
    // FIND STORE BY CODE (LIKE)
    // ----------------------
    public function findByStoreCode($code) {

        $conn = Connection::getInstance();
        $likeCode = "%$code%";

        $stmt = $conn->prepare("SELECT * FROM stores WHERE StoreCode LIKE ?");
        $stmt->bind_param("s", $likeCode);
        $stmt->execute();

        $result = $stmt->get_result();
        $stores = [];
        while ($row = $result->fetch_assoc()) {
            $stores[] = $row;
        }

        $result->free();
        $stmt->close();
        Connection::releaseConnection($conn);

        return $stores;
    }

    // ----------------------
    // CREATE STORE
    // ----------------------
    public function createStore($data) {

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

    // ----------------------
    // SINGLE UPDATE
    // ----------------------
    public function updateStoreByID($id, $data) {

        $conn = Connection::getInstance();

        $sql = "UPDATE stores 
                SET
                    StoreCode      = ?,
                    StoreName      = ?,
                    StoreAddress   = ?,
                    Code           = ?,
                    ContactPerson  = ?,
                    ContactNum     = ?,
                    Email          = ?
                WHERE StoreID = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssssssi",
            $data['StoreCode'],
            $data['StoreName'],
            $data['StoreAddress'],
            $data['Code'],
            $data['ContactPerson'],
            $data['ContactNum'],
            $data['Email'],
            $id
        );

        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        Connection::releaseConnection($conn);
        return $success;
    }


    // ----------------------
    // BATCH UPDATE (transaction + loop)
    // ----------------------
    public function updateStoreByBatch(array $stores) {

        if (empty($stores)) return false;

        try {
            Connection::beginTransaction();

            foreach ($stores as $store) {
                $this->updateStoreByID($store['StoreID'], $store);
            }

            Connection::commit();
            return true;
        } catch (Exception $e) {
            Connection::rollback();
            return false;
        }
    }

    // ----------------------
    // SINGLE DELETE
    // ----------------------
    public function deleteStoreByID($id) {

        $conn = Connection::getInstance();

        $stmt = $conn->prepare("DELETE FROM stores WHERE StoreID = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();

        $stmt->close();
        Connection::releaseConnection($conn);

        return $success;
    }

    // ----------------------
    // BATCH DELETE (transaction + loop)
    // ----------------------
    public function deleteStoreByBatch(array $ids) {

        if (empty($ids)) return false;

        try {
            Connection::beginTransaction();

            foreach ($ids as $id) {
                $this->deleteStoreByID($id);
            }

            Connection::commit();
            return true;
        } catch (Exception $e) {
            Connection::rollback();
            return false;
        }
    }

    // ----------------------
    // GET TOTAL COUNT
    // ----------------------
    public function getTotalCount() {
        
        $conn = Connection::getInstance();

        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM stores s 
            LEFT JOIN statuses st ON s.StatusID = st.StatusID
        ");
        
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
        $totalPages = ceil($totalRecords / $limit);
        
        return [
            'current_page' => $currentPage,
            'per_page' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'from' => (($currentPage - 1) * $limit) + 1,
            'to' => min($currentPage * $limit, $totalRecords)
        ];
    }
}
