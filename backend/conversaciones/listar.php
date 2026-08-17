<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

// Si el ultimo mensaje es de hoy, mostramos la hora; si no, la fecha
// corta. Evita tener que replicar "Ayer"/"Lun" del mock original.
function formatearHoraRelativa(?string $fecha): string
{
    if ($fecha === null) {
        return '';
    }
    $timestamp = strtotime($fecha);
    if (date('Y-m-d') === date('Y-m-d', $timestamp)) {
        return date('H:i', $timestamp);
    }
    return date('d/m', $timestamp);
}

$usuario = obtenerUsuarioAutenticado($conexion);

$stmt = $conexion->prepare(
    'SELECT c.id, cl.nombre, cl.email, cl.telefono, cl.empresa, c.estado,
            (SELECT texto FROM mensajes WHERE conversacion_id = c.id ORDER BY fecha_creacion DESC LIMIT 1) AS ultimo_texto,
            COALESCE(
              (SELECT fecha_creacion FROM mensajes WHERE conversacion_id = c.id ORDER BY fecha_creacion DESC LIMIT 1),
              c.fecha_creacion
            ) AS ultima_fecha
     FROM conversaciones c
     INNER JOIN clientes cl ON cl.id = c.cliente_id
     WHERE c.usuario_id = ?
     ORDER BY ultima_fecha DESC'
);
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
$resultado = $stmt->get_result();

$conversaciones = [];
while ($fila = $resultado->fetch_assoc()) {
    $conversaciones[] = [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'email' => $fila['email'],
        'telefono' => $fila['telefono'],
        'empresa' => $fila['empresa'],
        'estado' => $fila['estado'],
        'ultimoMensaje' => $fila['ultimo_texto'] ?? 'Conversación iniciada',
        'hora' => formatearHoraRelativa($fila['ultima_fecha']),
        'sinLeer' => 0,
    ];
}
$stmt->close();

echo json_encode($conversaciones);

$conexion->close();
