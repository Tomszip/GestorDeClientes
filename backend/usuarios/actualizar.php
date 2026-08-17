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
$email = trim($datos['email'] ?? '');
$rol = strtolower(trim($datos['rol'] ?? ''));
$estado = strtolower(trim($datos['estado'] ?? ''));

if ($id <= 0 || $nombre === '' || $email === ''
    || !in_array($rol, ['admin', 'usuario'], true)
    || !in_array($estado, ['activo', 'inactivo'], true)
) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos obligatorios o son inválidos.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'El email no es válido.']);
    exit;
}

// Evita que el email quede duplicado con OTRO usuario (con el mismo, esta bien).
$stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
$stmt->bind_param('si', $email, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['mensaje' => 'Ese email ya está en uso por otro usuario.']);
    exit;
}
$stmt->close();

// Un admin no puede quitarse a si mismo el rol ni desactivar su propia
// cuenta, para que el sistema nunca se quede sin ningun admin activo.
if ($id === $usuario['id'] && ($rol !== 'admin' || $estado !== 'activo')) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'No podés quitarte a vos mismo el rol de administrador ni desactivar tu propia cuenta.']);
    exit;
}

$stmt = $conexion->prepare('UPDATE usuarios SET nombre = ?, email = ?, rol = ?, estado = ? WHERE id = ?');
$stmt->bind_param('ssssi', $nombre, $email, $rol, $estado, $id);
$stmt->execute();
$filasAfectadas = $stmt->affected_rows;
$stmt->close();

if ($filasAfectadas === 0) {
    $verificar = $conexion->prepare('SELECT id FROM usuarios WHERE id = ?');
    $verificar->bind_param('i', $id);
    $verificar->execute();
    $existe = $verificar->get_result()->fetch_assoc();
    $verificar->close();

    if (!$existe) {
        http_response_code(404);
        echo json_encode(['mensaje' => 'Usuario no encontrado.']);
        exit;
    }
}

echo json_encode([
    'id' => $id,
    'nombre' => $nombre,
    'email' => $email,
    'rol' => ucfirst($rol),
    'estado' => ucfirst($estado),
]);

$conexion->close();
