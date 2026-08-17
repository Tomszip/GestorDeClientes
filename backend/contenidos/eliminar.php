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
    echo json_encode(['mensaje' => 'Falta el id del contenido.']);
    exit;
}

// El dueño puede borrar su propio contenido; un admin puede borrar
// cualquiera (moderacion basica, Parte 2). Esto es una baja real,
// distinta del futuro ocultamiento por moderacion (estado = "oculto").
if ($usuario['rol'] === 'admin') {
    $stmt = $conexion->prepare('SELECT id, ruta_archivo FROM contenidos WHERE id = ?');
    $stmt->bind_param('i', $id);
} else {
    $stmt = $conexion->prepare('SELECT id, ruta_archivo FROM contenidos WHERE id = ? AND usuario_id = ?');
    $stmt->bind_param('ii', $id, $usuario['id']);
}
$stmt->execute();
$contenido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contenido) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Contenido no encontrado.']);
    exit;
}

$stmt = $conexion->prepare('DELETE FROM contenidos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

if ($contenido['ruta_archivo']) {
    $ruta = __DIR__ . '/../uploads/' . $contenido['ruta_archivo'];
    if (file_exists($ruta)) {
        unlink($ruta);
    }
}

echo json_encode(['mensaje' => 'Contenido eliminado correctamente.']);

$conexion->close();
