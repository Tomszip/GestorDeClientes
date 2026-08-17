<?php
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
$email = trim($datos['email'] ?? '');
$password = $datos['password'] ?? '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Completá todos los campos.']);
    exit;
}

$stmt = $conexion->prepare('SELECT id, nombre, email, contrasena, rol, estado FROM usuarios WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario || !password_verify($password, $usuario['contrasena'])) {
    http_response_code(401);
    echo json_encode(['mensaje' => 'Email o contraseña incorrectos.']);
    exit;
}

if ($usuario['estado'] === 'inactivo') {
    http_response_code(403);
    echo json_encode(['mensaje' => 'Tu cuenta está inactiva. Contactá al administrador.']);
    exit;
}

$token = bin2hex(random_bytes(32));
$expiracion = date('Y-m-d H:i:s', strtotime('+1 day'));

$stmt = $conexion->prepare('INSERT INTO tokens_sesion (usuario_id, token, fecha_expiracion) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $usuario['id'], $token, $expiracion);
$stmt->execute();
$stmt->close();

$stmt = $conexion->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?');
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
$stmt->close();

echo json_encode([
    'token' => $token,
    'user' => [
        'id' => (int) $usuario['id'],
        'nombre' => $usuario['nombre'],
        'email' => $usuario['email'],
        'rol' => ucfirst($usuario['rol']),
        'estado' => ucfirst($usuario['estado']),
    ],
]);

$conexion->close();
