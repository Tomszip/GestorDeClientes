<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);
$conversacionId = (int) ($_GET['conversacionId'] ?? 0);

if ($conversacionId <= 0) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Falta la conversación.']);
    exit;
}

$stmt = $conexion->prepare('SELECT id FROM conversaciones WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $conversacionId, $usuario['id']);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existe) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Conversación no encontrada.']);
    exit;
}

$stmt = $conexion->prepare(
    'SELECT id, texto, es_propio, fecha_creacion FROM mensajes WHERE conversacion_id = ? ORDER BY fecha_creacion ASC'
);
$stmt->bind_param('i', $conversacionId);
$stmt->execute();
$resultado = $stmt->get_result();

$mensajes = [];
while ($fila = $resultado->fetch_assoc()) {
    $mensajes[] = [
        'id' => (int) $fila['id'],
        'texto' => $fila['texto'],
        'hora' => date('H:i', strtotime($fila['fecha_creacion'])),
        'esPropio' => (bool) $fila['es_propio'],
        'conversacionId' => $conversacionId,
    ];
}
$stmt->close();

echo json_encode($mensajes);

$conexion->close();
