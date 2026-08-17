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
    echo json_encode(['mensaje' => 'Falta el id del usuario.']);
    exit;
}

if ($id === $usuario['id']) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'No podés eliminar tu propia cuenta.']);
    exit;
}

$stmt = $conexion->prepare('DELETE FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Usuario no encontrado.']);
    exit;
}

echo json_encode(['mensaje' => 'Usuario eliminado correctamente.']);

$conexion->close();
