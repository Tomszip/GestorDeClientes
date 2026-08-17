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

$contenidoId = (int) ($datos['contenidoId'] ?? 0);
$usuarioIds = $datos['usuarioIds'] ?? [];

if ($contenidoId <= 0 || !is_array($usuarioIds) || count($usuarioIds) === 0) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos para compartir.']);
    exit;
}

// Solo el dueño del contenido lo puede compartir.
$stmt = $conexion->prepare('SELECT id FROM contenidos WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $contenidoId, $usuario['id']);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existe) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Contenido no encontrado.']);
    exit;
}

$compartidos = 0;
foreach ($usuarioIds as $destinatarioId) {
    $destinatarioId = (int) $destinatarioId;
    if ($destinatarioId <= 0 || $destinatarioId === $usuario['id']) {
        continue;
    }

    // INSERT IGNORE aprovecha la restriccion UNIQUE(contenido_id, usuario_id)
    // de la tabla: si ya estaba compartido con ese usuario, no falla, solo
    // no hace nada.
    $stmt = $conexion->prepare(
        'INSERT IGNORE INTO contenidos_compartidos (contenido_id, usuario_id) VALUES (?, ?)'
    );
    $stmt->bind_param('ii', $contenidoId, $destinatarioId);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $compartidos++;
    }
    $stmt->close();
}

echo json_encode(['mensaje' => 'Contenido compartido correctamente.', 'compartidos' => $compartidos]);

$conexion->close();
