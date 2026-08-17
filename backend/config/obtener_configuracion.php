<?php
require_once __DIR__ . '/conexion.php';

// Publico a proposito (sin token): el nombre del sitio y el tema activo
// hacen falta ANTES de loguearse (pantallas de login/registro), y no son
// datos sensibles.
$stmt = $conexion->prepare(
    'SELECT c.nombre_sitio, t.id AS tema_id, t.nombre AS tema_nombre, t.archivo_css
     FROM configuracion c
     INNER JOIN temas t ON t.id = c.tema_activo_id
     LIMIT 1'
);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fila) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'El sitio todavía no tiene configuración inicial.']);
    exit;
}

echo json_encode([
    'nombreSitio' => $fila['nombre_sitio'],
    'temaActivo' => [
        'id' => (int) $fila['tema_id'],
        'nombre' => $fila['tema_nombre'],
        'archivoCss' => $fila['archivo_css'],
    ],
]);

$conexion->close();
