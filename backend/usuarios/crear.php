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
$email = trim($datos['email'] ?? '');
$password = $datos['password'] ?? '';
$rol = strtolower(trim($datos['rol'] ?? 'usuario'));

if ($nombre === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Nombre, email y contraseña son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'El email no es válido.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'La contraseña debe tener al menos 6 caracteres.']);
    exit;
}

if (!in_array($rol, ['admin', 'usuario'], true)) {
    $rol = 'usuario';
}

$stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['mensaje' => 'Ese email ya está registrado.']);
    exit;
}
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conexion->prepare('INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $nombre, $email, $hash, $rol);
$stmt->execute();
$usuarioId = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'id' => $usuarioId,
    'nombre' => $nombre,
    'email' => $email,
    'rol' => ucfirst($rol),
    'estado' => 'Activo',
    'ultimoAcceso' => null,
]);

$conexion->close();
