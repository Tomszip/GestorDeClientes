<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);
requerirAdmin($usuario);

$stmt = $conexion->prepare(
    'SELECT c.id, c.titulo, c.texto, c.tipo,
            c.ruta_archivo AS rutaArchivo,
            c.fecha_creacion AS fechaCreacion,
            c.estado,
            u.nombre AS autor,
            u.email AS autorEmail,
            cat.nombre AS categoriaNombre
     FROM contenidos c
     INNER JOIN usuarios u ON u.id = c.usuario_id
     LEFT JOIN categorias cat ON cat.id = c.categoria_id
     ORDER BY c.fecha_creacion DESC'
);
$stmt->execute();
$resultado = $stmt->get_result();

$contenidos = [];
while ($fila = $resultado->fetch_assoc()) {
    $fila['id'] = (int) $fila['id'];
    $fila['estado'] = ucfirst($fila['estado']);
    $contenidos[] = $fila;
}
$stmt->close();

echo json_encode($contenidos);

$conexion->close();
