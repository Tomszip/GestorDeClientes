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
$nombre = trim($datos['nombre'] ?? '');
$email = trim($datos['email'] ?? '');
$telefono = trim($datos['telefono'] ?? '');
$empresa = trim($datos['empresa'] ?? '');
$ubicacion = trim($datos['ubicacion'] ?? '');

if ($id <= 0 || $nombre === '' || $email === '' || $telefono === '' || $empresa === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Nombre, email, teléfono y empresa son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'El email no es válido.']);
    exit;
}

// La ubicacion es el unico dato opcional del cliente (ver Client en
// crm.models.ts: es el unico campo declarado con "?").
$ubicacionParaGuardar = $ubicacion === '' ? null : $ubicacion;

$stmt = $conexion->prepare(
    'UPDATE clientes SET nombre = ?, email = ?, telefono = ?, empresa = ?, ubicacion = ?
     WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('sssssii', $nombre, $email, $telefono, $empresa, $ubicacionParaGuardar, $id, $usuario['id']);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

// affected_rows en 0 puede significar "no existe / no es tuyo" o
// "existe pero no cambio ningun valor". Los distinguimos con una
// consulta aparte para no devolver un 404 incorrecto.
if ($filasAfectadas === 0) {
    $verificar = $conexion->prepare('SELECT id FROM clientes WHERE id = ? AND usuario_id = ?');
    $verificar->bind_param('ii', $id, $usuario['id']);
    $verificar->execute();
    $existe = $verificar->get_result()->fetch_assoc();
    $verificar->close();

    if (!$existe) {
        http_response_code(404);
        echo json_encode(['mensaje' => 'Cliente no encontrado.']);
        exit;
    }
}

echo json_encode([
    'id' => $id,
    'nombre' => $nombre,
    'email' => $email,
    'telefono' => $telefono,
    'empresa' => $empresa,
    'ubicacion' => $ubicacionParaGuardar,
]);

$conexion->close();
