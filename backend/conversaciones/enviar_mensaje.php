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

$conversacionId = (int) ($datos['conversacionId'] ?? 0);
$texto = trim($datos['texto'] ?? '');

if ($conversacionId <= 0 || $texto === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos del mensaje.']);
    exit;
}

$stmt = $conexion->prepare('SELECT id, estado FROM conversaciones WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $conversacionId, $usuario['id']);
$stmt->execute();
$conversacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conversacion) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Conversación no encontrada.']);
    exit;
}

if ($conversacion['estado'] === 'cerrado') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'No se pueden enviar mensajes a una conversación cerrada.']);
    exit;
}

// Todos los mensajes los escribe el usuario del CRM: los "clientes" son
// fichas de contacto, no tienen cuenta propia para responder en el sistema.
$stmt = $conexion->prepare('INSERT INTO mensajes (conversacion_id, texto, es_propio) VALUES (?, ?, 1)');
$stmt->bind_param('is', $conversacionId, $texto);
$stmt->execute();
$mensajeId = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'id' => $mensajeId,
    'texto' => $texto,
    'hora' => date('H:i'),
    'esPropio' => true,
    'conversacionId' => $conversacionId,
]);

$conexion->close();
