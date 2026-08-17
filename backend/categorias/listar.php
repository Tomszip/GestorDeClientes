<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

// Cualquier usuario autenticado puede leer la lista (la necesita el
// selector de categoría al subir contenido). Crear/editar/borrar sí es
// exclusivo del admin, ver crear.php/actualizar.php/eliminar.php.
$usuario = obtenerUsuarioAutenticado($conexion);

$stmt = $conexion->prepare('SELECT id, nombre, descripcion FROM categorias ORDER BY nombre ASC');
$stmt->execute();
$resultado = $stmt->get_result();

$categorias = [];
while ($fila = $resultado->fetch_assoc()) {
    $categorias[] = [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'descripcion' => $fila['descripcion'],
    ];
}
$stmt->close();

echo json_encode($categorias);

$conexion->close();
