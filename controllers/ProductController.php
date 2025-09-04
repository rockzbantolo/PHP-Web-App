<?php

namespace Mws\Controllers;

use Mws\Models\Products;
use Mws\Helpers\Response;
use Throwable;

class ProductController
{
    private Products $productModel;

    public function __construct() {
        $this->productModel = new Products();
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