<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);
requerirAdmin($usuario);

$stmt = $conexion->prepare(
    'SELECT id, nombre, email, rol, estado, ultimo_acceso
     FROM usuarios
     ORDER BY fecha_registro DESC'
);
$stmt->execute();
$resultado = $stmt->get_result();

$usuarios = [];
while ($fila = $resultado->fetch_assoc()) {
    $usuarios[] = [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'email' => $fila['email'],
        'rol' => ucfirst($fila['rol']),
        'estado' => ucfirst($fila['estado']),
        'ultimoAcceso' => $fila['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($fila['ultimo_acceso'])) : null,
    ];
}
$stmt->close();

echo json_encode($usuarios);

$conexion->close();
