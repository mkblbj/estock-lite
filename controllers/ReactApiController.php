<?php

namespace Grocy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReactApiController extends BaseApiController
{
    public function GetProducts(Request $request, Response $response, array $args)
    {
        $products = $this->getDatabase()->products()->orderBy('name')->fetchAll();
        return $this->outputJson($response, $products);
    }
    
    public function GetProductDetails(Request $request, Response $response, array $args)
    {
        $productId = $args['productId'];
        $product = $this->getDatabase()->products()->where('id', $productId)->fetch();
        
        if ($product === null) {
            return $this->outputJson($response, ['error' => 'Product not found'], 404);
        }
        
        return $this->outputJson($response, $product);
    }
    
    public function GetStock(Request $request, Response $response, array $args)
    {
        $stockService = $this->getStockService();
        $currentStock = $stockService->GetCurrentStock();
        return $this->outputJson($response, $currentStock);
    }
} 