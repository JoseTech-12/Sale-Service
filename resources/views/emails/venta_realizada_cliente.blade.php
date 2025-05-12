<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta Realizada</title>
</head>
<body>
    <h1>¡Gracias por tu compra, {{ $client->nombre ?? 'Cliente' }}!</h1>

    <p>Detalles de la venta:</p>

<p><strong>Fecha de la venta:</strong> {{ $sale->created_at }}</p>
<p><strong>Total:</strong> ${{ number_format($sale->total, 2) }}</p>




    <h2>Productos comprados:</h2>
    <ul>
        @foreach($products as $product)
            <li>{{ $product['nombre'] }} - ${{ number_format($product['precio_unitario'], 2) }} x {{ $product['cantidad'] }} = ${{ number_format($product['subtotal'], 2) }}</li>
        @endforeach
    </ul>

    <p>¡Esperamos que disfrutes tu compra!</p>
</body>
</html>
