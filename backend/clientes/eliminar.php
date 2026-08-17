<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$usuario = obtenerUsuarioAutenticado($conexion);
$datos = json_decode(file_get_contents('php://input'), true);
$id = (int) ($datos['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Falta el id del cliente.']);
    exit;
}

$stmt = $conexion->prepare('DELETE FROM clientes WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuario['id']);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Cliente no encontrado.']);
    exit;
}

echo json_encode(['mensaje' => 'Cliente eliminado correctamente.']);

$conexion->close();
