<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);

$stmt = $conexion->prepare(
    'SELECT id, nombre, email, telefono, empresa, ubicacion, estado,
            ultimo_contacto, valor_negocios
     FROM clientes
     WHERE usuario_id = ?
     ORDER BY fecha_creacion DESC'
);
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
$resultado = $stmt->get_result();

// El frontend ya tenia definido su propio formato de visualizacion
// (estado capitalizado, fecha DD/MM/AAAA, moneda con separador de miles)
// desde los datos de muestra originales, asi que lo replicamos aca para
// no tener que tocar el HTML/CSS que ya estaba armado.
$clientes = [];
while ($fila = $resultado->fetch_assoc()) {
    $clientes[] = [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'email' => $fila['email'],
        'telefono' => $fila['telefono'],
        'empresa' => $fila['empresa'],
        'ubicacion' => $fila['ubicacion'],
        'estado' => ucfirst($fila['estado']),
        'ultimoContacto' => $fila['ultimo_contacto'] ? date('d/m/Y', strtotime($fila['ultimo_contacto'])) : null,
        'valorNegocios' => $fila['valor_negocios'] !== null
            ? '$' . number_format((float) $fila['valor_negocios'], 0, ',', '.')
            : null,
    ];
}
$stmt->close();

echo json_encode($clientes);

$conexion->close();
