<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$usuario = obtenerUsuarioAutenticado($conexion);

$termino = trim($_GET['q'] ?? '');

if ($termino === '') {
    echo json_encode([]);
    exit;
}

$like = '%' . $termino . '%';

// Excluye al propio usuario autenticado de los resultados: no tiene
// sentido "compartir contenido" con uno mismo.
$stmt = $conexion->prepare(
    'SELECT id, nombre, email
     FROM usuarios
     WHERE (nombre LIKE ? OR email LIKE ?)
       AND id != ?
       AND estado = "activo"
     ORDER BY nombre
     LIMIT 10'
);
$stmt->bind_param('ssi', $like, $like, $usuario['id']);
$stmt->execute();
$resultado = $stmt->get_result();

$usuarios = [];
while ($fila = $resultado->fetch_assoc()) {
    $fila['id'] = (int) $fila['id'];
    $usuarios[] = $fila;
}
$stmt->close();

echo json_encode($usuarios);

$conexion->close();
