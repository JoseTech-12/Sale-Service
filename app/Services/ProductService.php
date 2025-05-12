<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('PRODUCT_SERVICE_URL', 'http://localhost:8002/api');
    }

    public function getProduct($id)
    {
        try {
            $token = request()->bearerToken();
            $response = Http::withToken($token)->get("{$this->baseUrl}/showProduct/{$id}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("ProductService Error: {$response->status()} - {$response->body()}");
        } catch (\Exception $e) {
            Log::error("ProductService Exception: " . $e->getMessage());
        }

        return null;
    }

    public function updateStock($id, $cantidad)
    {
        $product = $this->getProduct($id);

        // Validar que $product sea un array y tenga clave 'stock'
        if (is_array($product) && isset($product['stock']) && $product['stock'] >= $cantidad) {
            $newStock = $product['stock'] - $cantidad;

            $response = Http::put("{$this->baseUrl}/updateProductStock/{$id}", [
                'stock' => $newStock
            ]);

            return $response->successful();
        }

        return false;
    }

    public function restoreStock($id, $cantidad)
    {
        $response = Http::put("{$this->baseUrl}/restore-stock/{$id}", [
            'cantidad' => $cantidad
        ]);

        if ($response->failed()) {
            Log::error("Error al restaurar el stock del producto ID {$id}: {$response->body()}");
            throw new \Exception('Error al restaurar el stock del producto.');
        }

        return true;
    }
}
