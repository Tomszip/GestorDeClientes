<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);
requerirAdmin($usuario);

$stmt = $conexion->prepare('SELECT id, nombre, archivo_css, activo FROM temas ORDER BY id ASC');
$stmt->execute();
$resultado = $stmt->get_result();

$temas = [];
while ($fila = $resultado->fetch_assoc()) {
    $temas[] = [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'archivoCss' => $fila['archivo_css'],
        'activo' => (bool) $fila['activo'],
    ];
}
$stmt->close();

echo json_encode($temas);

$conexion->close();
