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
$nombre = trim($datos['nombre'] ?? '');
$descripcion = trim($datos['descripcion'] ?? '');

if ($nombre === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'El nombre es obligatorio.']);
    exit;
}

$stmt = $conexion->prepare('SELECT id FROM categorias WHERE nombre = ?');
$stmt->bind_param('s', $nombre);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['mensaje' => 'Ya existe una categoría con ese nombre.']);
    exit;
}
$stmt->close();

$descripcionParaGuardar = $descripcion === '' ? null : $descripcion;

$stmt = $conexion->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)');
$stmt->bind_param('ss', $nombre, $descripcionParaGuardar);
$stmt->execute();
$categoriaId = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'id' => $categoriaId,
    'nombre' => $nombre,
    'descripcion' => $descripcionParaGuardar,
]);

$conexion->close();
