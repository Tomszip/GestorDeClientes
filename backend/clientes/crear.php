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

$nombre = trim($datos['nombre'] ?? '');
$email = trim($datos['email'] ?? '');
$telefono = trim($datos['telefono'] ?? '');
$empresa = trim($datos['empresa'] ?? '');
$ubicacion = trim($datos['ubicacion'] ?? '');

if ($nombre === '' || $email === '' || $telefono === '' || $empresa === '') {
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

// Todo cliente nuevo arranca en estado "nuevo" (Parte del ciclo de vida
// propio del CRM: nuevo -> activo -> pendiente/inactivo se maneja despues
// a mano, no hay una regla automatica pedida por la consigna para esto).
$stmt = $conexion->prepare(
    'INSERT INTO clientes (usuario_id, nombre, email, telefono, empresa, ubicacion, estado)
     VALUES (?, ?, ?, ?, ?, ?, "nuevo")'
);
$stmt->bind_param('isssss', $usuario['id'], $nombre, $email, $telefono, $empresa, $ubicacionParaGuardar);
$stmt->execute();
$clienteId = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'id' => $clienteId,
    'nombre' => $nombre,
    'email' => $email,
    'telefono' => $telefono,
    'empresa' => $empresa,
    'ubicacion' => $ubicacionParaGuardar,
    'estado' => 'Nuevo',
    'ultimoContacto' => null,
    'valorNegocios' => null,
]);

$conexion->close();
