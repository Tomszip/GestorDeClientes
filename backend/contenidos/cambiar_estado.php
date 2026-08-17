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
$estado = strtolower(trim($datos['estado'] ?? ''));

if ($id <= 0 || !in_array($estado, ['visible', 'oculto'], true)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Datos inválidos.']);
    exit;
}

$stmt = $conexion->prepare('UPDATE contenidos SET estado = ? WHERE id = ?');
$stmt->bind_param('si', $estado, $id);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    $verificar = $conexion->prepare('SELECT id FROM contenidos WHERE id = ?');
    $verificar->bind_param('i', $id);
    $verificar->execute();
    $existe = $verificar->get_result()->fetch_assoc();
    $verificar->close();

    if (!$existe) {
        http_response_code(404);
        echo json_encode(['mensaje' => 'Contenido no encontrado.']);
        exit;
    }
}

echo json_encode(['mensaje' => 'Estado actualizado.', 'estado' => ucfirst($estado)]);

$conexion->close();
