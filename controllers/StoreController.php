<?php

namespace Mws\Controllers;

use Mws\Models\Store;
use Mws\Helpers\Response;
use Throwable;

class StoreController
{
    private Store $storeModel;

    public function __construct() {
        $this->storeModel = new Store();
    }

    public static function initRoutes($router)
    {
        $router->register('GET', '/Store', [self::class, 'getAllStore']);
        $router->register('GET', '/Store/{id}', [self::class, 'getStoreById']);
        $router->register('POST', '/Store', [self::class, 'saveStore']);
        $router->register('PUT', '/Store', [self::class, 'updateStore']);
        $router->register('DELETE', '/Store', [self::class, 'deleteStore']);
    }

    public function getProductById($id) {
        try {
            $user = $this->storeModel->findStoreById($id);
            return $user
                ? Response::success($user, "Store found")
                : Response::notFound("Store with ID $id");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch Store", 500, $e->getMessage());
        }
    }

}