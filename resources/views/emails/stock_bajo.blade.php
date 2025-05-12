<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Stock bajo</title>
</head>
<body>
    <h1>⚠️ Alerta de stock bajo</h1>
    <p>El producto <strong>{{ $producto['nombre'] }}</strong> tiene un stock crítico.</p>
    <p>Stock restante: <strong>{{ $producto['stock'] }}</strong></p>
    <p>ID del producto: {{ $producto['id'] }}</p>
</body>
</html>
