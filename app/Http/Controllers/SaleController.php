<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalesItem;
use App\Services\ClientService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\VentaRealizadaCliente;
use Illuminate\Support\Facades\Log;
use App\Mail\StockBajo;

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
        $sales = Sale::with('items')->get();
        $data = $sales->map(function ($sale) {

            $client = $this->clientService->getClient($sale->client_id);

            $items = $sale->items ?? collect();

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
        // Obtener cliente desde microservicio
        $client = $this->clientService->getClient($request->client_id);

        if (!$client) {
            return response()->json([
                'message' => 'cliente no encontrado'
            ], 404);
        }

        try {
            $productos_detalle = [];

            DB::beginTransaction(); // 👉 Inicia la transacción

            $sale = Sale::create([
                'client_id' => $client['id'],
                'total' => 0
            ]);

            $total = 0;

            foreach ($request->products as $productData) {
                $product = $this->productService->getProduct($productData['product_id']);
                if (!$product) {
                    throw new \Exception('Producto no encontrado');
                }

                if ($product['stock'] < $productData['cantidad']) {
                    throw new \Exception('Stock insuficiente para el producto ' . $product['nombre']);
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



                $productoActualizado = $this->productService->getProduct($product['id']);


                if ($productoActualizado && isset($productoActualizado['stock']) && $productoActualizado['stock'] <= 2) {
                    Mail::to('jp898467@gmail.com')->send(new StockBajo($productoActualizado));
                }


                $productos_detalle[] = [
                    'id' => $product['id'],
                    'nombre' => $product['nombre'],
                    'precio_unitario' => $product['precio'],
                    'cantidad' => $productData['cantidad'],
                    'subtotal' => $subtotal
                ];
            }

            $sale->update(['total' => $total]);
            $sale->refresh();


            Log::info('Enviando correo a: ' . $client['email']);


            DB::commit(); // 👉 Confirma la transacción
            Mail::to($client['email'])->send(new VentaRealizadaCliente($client, $sale, $productos_detalle));
            return response()->json([
                'message' => 'Venta realizada con éxito',
                'sale' => $sale,
                'client' => $client,
                'products' => $productos_detalle
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // ❌ Reversar todo si ocurre un error
            Log::error('Error al enviar correo o guardar venta: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al realizar la venta: ' . $e->getMessage()
            ], 500);
        }
    }




    public function show(Request $request, string $id)
    {
        $sale = Sale::with('items')->find($id);

        if (!$sale) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        // Obtener cliente desde microservicio
        $client = $this->clientService->getClient($sale->client_id);

        // Obtener productos desde microservicio
        $productos = $sale->items->map(function ($item) {
            $producto = $this->productService->getProduct($item->product_id);

            return [
                'producto_id' => $item->product_id,
                'nombre' => $producto['nombre'] ?? 'Desconocido',
                'cantidad' => $item->cantidad,
                'precio_unitario' => $item->precio_unitario,
                'subtotal' => $item->subtotal,
            ];
        });

        return response()->json([
            'id' => $sale->id,
            'total' => $sale->total,
            'fecha' => $sale->created_at->toDateTimeString(),
            'cliente' => [
                'id' => $client['id'] ?? null,
                'nombre' => $client['nombre'] ?? 'Desconocido',
                'correo' => $client['email'] ?? null
            ],
            'productos' => $productos
        ], 200);
    }




    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $sale = Sale::with('items')->find($id);

            if (!$sale) {
                return response()->json(['message' => 'Venta no encontrada'], 404);
            }

            // Validar nuevo cliente si se cambió
            $client = $this->clientService->getClient($request->client_id);
            if (!$client) {
                return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            // 1. Revertir stock anterior
            foreach ($sale->items as $item) {
                $this->productService->restoreStock($item->product_id, $item->cantidad);
            }

            // 2. Eliminar ítems actuales
            SalesItem::where('sale_id', $sale->id)->delete();

            // 3. Registrar nuevos ítems
            $total = 0;
            $productos_detalle = [];

            foreach ($request->products as $productData) {
                $product = $this->productService->getProduct($productData['product_id']);

                if (!$product) {
                    throw new \Exception('Producto no encontrado');
                }

                if ($product['stock'] < $productData['cantidad']) {
                    throw new \Exception('Stock insuficiente para el producto ' . $product['nombre']);
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

            // 4. Actualizar la venta
            $sale->update([
                'client_id' => $client['id'],
                'total' => $total
            ]);

            $sale = $sale->refresh(); // no cargar items

            return response()->json([
                'message' => 'Venta actualizada con éxito',
                'sale' => [
                    'id' => $sale->id,
                    'client_id' => $sale->client_id,
                    'total' => $sale->total,
                    'fecha' => $sale->created_at->toDateTimeString()
                ],
                'client' => $client,
                'products' => $productos_detalle
            ]);
        });
    }



    public function destroy(string $id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        // Revertir stock de los productos
        foreach ($sale->items as $item) {
            $this->productService->restoreStock($item->product_id, $item->cantidad);
        }

        // Eliminar la venta y sus ítems
        $sale->items()->delete();
        $sale->delete();

        return response()->json(['message' => 'Venta eliminada con éxito'], 200);
    }
}
