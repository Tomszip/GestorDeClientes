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

$conversacionId = (int) ($datos['conversacionId'] ?? 0);
$estado = trim($datos['estado'] ?? '');

if ($conversacionId <= 0 || !in_array($estado, ['nuevo', 'en-curso', 'cerrado'], true)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Datos inválidos.']);
    exit;
}

$stmt = $conexion->prepare('UPDATE conversaciones SET estado = ? WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('sii', $estado, $conversacionId, $usuario['id']);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    $verificar = $conexion->prepare('SELECT id FROM conversaciones WHERE id = ? AND usuario_id = ?');
    $verificar->bind_param('ii', $conversacionId, $usuario['id']);
    $verificar->execute();
    $existe = $verificar->get_result()->fetch_assoc();
    $verificar->close();

    if (!$existe) {
        http_response_code(404);
        echo json_encode(['mensaje' => 'Conversación no encontrada.']);
        exit;
    }
}

echo json_encode(['mensaje' => 'Estado actualizado.', 'estado' => $estado]);

$conexion->close();
