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
$clienteId = (int) ($datos['clienteId'] ?? 0);

if ($clienteId <= 0) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Falta el cliente.']);
    exit;
}

// El cliente elegido tiene que ser tuyo.
$stmt = $conexion->prepare('SELECT id, nombre, email, telefono, empresa FROM clientes WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $clienteId, $usuario['id']);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Cliente no encontrado.']);
    exit;
}

// Si ya existe una conversacion con ese cliente, la reutilizamos en vez
// de crear una duplicada (cada cliente tiene un solo hilo de conversacion).
$stmt = $conexion->prepare('SELECT id, estado FROM conversaciones WHERE cliente_id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $clienteId, $usuario['id']);
$stmt->execute();
$existente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existente) {
    $conversacionId = (int) $existente['id'];
    $estado = $existente['estado'];
} else {
    $stmt = $conexion->prepare('INSERT INTO conversaciones (cliente_id, usuario_id, estado) VALUES (?, ?, "nuevo")');
    $stmt->bind_param('ii', $clienteId, $usuario['id']);
    $stmt->execute();
    $conversacionId = $stmt->insert_id;
    $stmt->close();
    $estado = 'nuevo';
}

echo json_encode([
    'id' => $conversacionId,
    'nombre' => $cliente['nombre'],
    'email' => $cliente['email'],
    'telefono' => $cliente['telefono'],
    'empresa' => $cliente['empresa'],
    'estado' => $estado,
    'ultimoMensaje' => 'Conversación iniciada',
    'hora' => '',
    'sinLeer' => 0,
]);

$conexion->close();
