<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/autenticacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$usuario = obtenerUsuarioAutenticado($conexion);
requerirAdmin($usuario);

$datos = json_decode(file_get_contents('php://input'), true);
$nombreSitio = trim($datos['nombreSitio'] ?? '');
$temaActivoId = (int) ($datos['temaActivoId'] ?? 0);

if ($nombreSitio === '' || $temaActivoId <= 0) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos obligatorios.']);
    exit;
}

$stmt = $conexion->prepare('SELECT id FROM temas WHERE id = ?');
$stmt->bind_param('i', $temaActivoId);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existe) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'El tema elegido no existe.']);
    exit;
}

// Mantiene la columna temas.activo coherente con configuracion.tema_activo_id
// (solo uno puede estar marcado como activo a la vez).
$conexion->query('UPDATE temas SET activo = 0');
$stmt = $conexion->prepare('UPDATE temas SET activo = 1 WHERE id = ?');
$stmt->bind_param('i', $temaActivoId);
$stmt->execute();
$stmt->close();

$stmt = $conexion->prepare('UPDATE configuracion SET nombre_sitio = ?, tema_activo_id = ?');
$stmt->bind_param('si', $nombreSitio, $temaActivoId);
$stmt->execute();
$stmt->close();

echo json_encode(['mensaje' => 'Configuración actualizada.', 'nombreSitio' => $nombreSitio, 'temaActivoId' => $temaActivoId]);

$conexion->close();
