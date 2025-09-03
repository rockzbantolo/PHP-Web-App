<?php

namespace Mws\Controllers;

use Mws\Models\Products;
use Mws\Helpers\Response;
use Mws\Core\RouteControllerInterface;
use Throwable;

class ProductController implements RouteControllerInterface
{
    private Products $productModel;

    public function __construct() {
        $this->productModel = new Products();
    }

    public static function initRoutes($router)
    {
        $router->register('GET', '/Product', [self::class, 'getAllProducts']);
        $router->register('GET', '/Product/{id}', [self::class, 'getProductById']);
        $router->register('POST', '/Product', [self::class, 'saveProducts']);
        $router->register('PUT', '/Product', [self::class, 'updateProducts']);
        $router->register('DELETE', '/Product', [self::class, 'deleteProducts']);
    }

    public function getProductById($id) {
        try {
            $user = $this->productModel->findProductById($id);
            return $user
                ? Response::success($user, "Product found")
                : Response::notFound("Product with ID $id");
        } catch (Throwable $e) {
            return Response::error("Failed to fetch Product", 500, $e->getMessage());
        }
    }

}