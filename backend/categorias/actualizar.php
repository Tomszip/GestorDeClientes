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
$nombre = trim($datos['nombre'] ?? '');
$descripcion = trim($datos['descripcion'] ?? '');

if ($id <= 0 || $nombre === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos obligatorios.']);
    exit;
}

$stmt = $conexion->prepare('SELECT id FROM categorias WHERE nombre = ? AND id != ?');
$stmt->bind_param('si', $nombre, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['mensaje' => 'Ya existe otra categoría con ese nombre.']);
    exit;
}
$stmt->close();

$descripcionParaGuardar = $descripcion === '' ? null : $descripcion;

$stmt = $conexion->prepare('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?');
$stmt->bind_param('ssi', $nombre, $descripcionParaGuardar, $id);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    $verificar = $conexion->prepare('SELECT id FROM categorias WHERE id = ?');
    $verificar->bind_param('i', $id);
    $verificar->execute();
    $existe = $verificar->get_result()->fetch_assoc();
    $verificar->close();

    if (!$existe) {
        http_response_code(404);
        echo json_encode(['mensaje' => 'Categoría no encontrada.']);
        exit;
    }
}

echo json_encode([
    'id' => $id,
    'nombre' => $nombre,
    'descripcion' => $descripcionParaGuardar,
]);

$conexion->close();
