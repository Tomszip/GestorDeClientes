<?php
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
$nombre = trim($datos['nombre'] ?? '');
$email = trim($datos['email'] ?? '');
$password = $datos['password'] ?? '';

if ($nombre === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Completá todos los campos.']);
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

$stmt = $conexion->prepare('INSERT INTO usuarios (nombre, email, contrasena) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $nombre, $email, $hash);
$stmt->execute();
$usuarioId = $stmt->insert_id;
$stmt->close();

$token = bin2hex(random_bytes(32));
$expiracion = date('Y-m-d H:i:s', strtotime('+1 day'));

$stmt = $conexion->prepare('INSERT INTO tokens_sesion (usuario_id, token, fecha_expiracion) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $usuarioId, $token, $expiracion);
$stmt->execute();
$stmt->close();

echo json_encode([
    'token' => $token,
    'user' => [
        'id' => $usuarioId,
        'nombre' => $nombre,
        'email' => $email,
        'rol' => 'Usuario',
        'estado' => 'Activo',
    ],
]);

$conexion->close();
