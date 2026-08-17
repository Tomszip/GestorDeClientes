<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$usuario = obtenerUsuarioAutenticado($conexion);
requerirAdmin($usuario);

$datos = json_decode(file_get_contents('php://input'), true);
$id = (int) ($datos['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Falta el id de la categoría.']);
    exit;
}

// contenidos.categoria_id tiene ON DELETE SET NULL: los contenidos que
// usaban esta categoría no se borran, solo quedan sin categorizar.
$stmt = $conexion->prepare('DELETE FROM categorias WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Categoría no encontrada.']);
    exit;
}

echo json_encode(['mensaje' => 'Categoría eliminada correctamente.']);

$conexion->close();
