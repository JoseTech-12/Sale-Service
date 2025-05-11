<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalesItem;
use App\Services\ClientService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class SaleController extends Controller
{

    protected $clientService;
    protected $productService;

    public function __construct(ClientService $clientService, ProductService $productService)
    {

        $this->clientService = $clientService;
        $this->productService = $productService;
    }

    public function index(Request $request)
    {




        $sales = Sale::with('items')->get(); // solo items están en este microservicio

        $data = $sales->map(function ($sale) {
            // Obtener cliente del microservicio
            $client = $this->clientService->getClient($sale->client_id);

            $items = $sale->items ?? collect();

            // Obtener productos del microservicio
            $productos = $items->map(function ($item) {
                $producto = $this->productService->getProduct($item->product_id);

                return [
                    'producto_id' => $item->product_id,
                    'nombre' => $producto['nombre'] ?? 'Desconocido',
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'subtotal' => $item->subtotal,
                ];
            });

            return [
                'id' => $sale->id,
                'total' => $sale->total,
                'fecha' => $sale->created_at->toDateTimeString(),
                'cliente' => [
                    'id' => $client['id'] ?? null,
                    'nombre' => $client['nombre'] ?? 'Desconocido',
                    'correo' => $client['email'] ?? null
                ],
                'productos' => $productos
            ];
        });

        return response()->json(['ventas' => $data], 200);
    }

    public function store(Request $request)
    {

        $client = $this->clientService->getClient($request->client_id);

        if (!$client) {
            return response()->json([
                'message' => 'cliente no encontrado'
            ], 404);
        }

        $sale = Sale::create([
            'client_id' => $client['id'],
            'total' => 0
        ]);

        $total = 0;

        foreach ($request->products as $productData) {
            $product = $this->productService->getProduct($productData['product_id']);
            if (!$product) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            if ($product['stock'] < $productData['cantidad']) {
                return response()->json(['error' => 'Stock insuficiente para el producto ' . $product['name']], 400);
            }
            $subtotal = $product['precio'] * $productData['cantidad'];
            $total += $subtotal;

            SalesItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product['id'],
                'cantidad' => $productData['cantidad'],
                'precio_unitario' => $product['precio'],
                'subtotal' => $subtotal
            ]);

            $this->productService->updateStock($product['id'], $productData['cantidad']);


            $productos_detalle[] = [
                'id' => $product['id'],
                'nombre' => $product['nombre'],
                'precio_unitario' => $product['precio'],
                'cantidad' => $productData['cantidad'],
                'subtotal' => $subtotal
            ];
        }



        $sale->update(['total' => $total]);
        return response()->json([
            'message' => 'Venta realizada con éxito',
            'sale' => $sale,
            'client' => $client,
            'products' => $productos_detalle
        ], 201);
    }




    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
        //
    }
}
