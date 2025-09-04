<?php

namespace Mws\Controllers;

use Mws\Models\Stores;
use Mws\Helpers\Response;
use Mws\Core\StatusCode;
use Throwable;

class StoreController
{
    private Stores $storeModel;

    public function __construct() {
        $this->storeModel = new Stores();
    }

    public function getAllStores() {
        try {

            $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

            $result = $this->storeModel->findAllStores($page, $limit);

            return Response::success(
                $result['data'],
                "Stores retrieved successfully",
                StatusCode::OK,
                $result['pagination']
            );
        } catch (Throwable $e) {
            return Response::error("Failed to fetch stores", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getStoreById($id) {
        try {
            $store = $this->storeModel->findStoreById($id);
            return $store
                ? Response::success($store, "Store found")
                : Response::notFound("Store with ID $id not found");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch store", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function saveStore($data) {
        try {
            $result = $this->storeModel->createStore($data);
            return $result['success']
                ? Response::success(['id' => $result['id']], "Store created successfully")
                : Response::error("Store creation failed", StatusCode::INTERNAL_SERVER_ERROR, $result['error']);
        } catch (Throwable $e) {
            return Response::error("Failed to create store", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function updateStore($id, $data) {
        try {
            $result = $this->storeModel->updateStoreByID($id, $data);
            return Response::success([], "Store updated successfully");
        } catch (Throwable $e) {
            return Response::error("Update failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function deleteStore($id) {
        try {
            $result = $this->storeModel->deleteStoreByID($id);
            return Response::success([], "Store deleted successfully");
        } catch (Throwable $e) {
            return Response::error("Delete failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function batchCreateStores($data) {
        try {
            if (!is_array($data) || empty($data)) {
                return Response::error("Invalid batch data", StatusCode::CONFLICT);
            }

            $results = [];
            foreach ($data as $item) {
                $results[] = $this->storeModel->createStore($item);
            }

            return Response::success($results, "Batch create complete");
        } catch (Throwable $e) {
            return Response::error("Batch create failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function batchUpdateStores($data) {
        try {
            $results = [];
            foreach ($data as $item) {
                if (isset($item['id'])) {
                    $results[] = $this->storeModel->updateStoreByID($item['id'], $item);
                }
            }
            return Response::success([], "Batch update complete");
        } catch (Throwable $e) {
            return Response::error("Batch update failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function batchDeleteStores($data) {
        try {
            $results = [];
            foreach ($data as $item) {
                if (isset($item['id'])) {
                    $results[] = $this->storeModel->deleteStoreByID($item['id']);
                }
            }
            return Response::success([], "Batch delete complete");
        } catch (Throwable $e) {
            return Response::error("Batch delete failed", StatusCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
