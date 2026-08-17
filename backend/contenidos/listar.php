<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);

$stmt = $conexion->prepare(
    'SELECT c.id, c.titulo, c.texto, c.tipo,
            c.ruta_archivo AS rutaArchivo,
            c.fecha_creacion AS fechaCreacion,
            c.categoria_id AS categoriaId,
            cat.nombre AS categoriaNombre
     FROM contenidos c
     LEFT JOIN categorias cat ON cat.id = c.categoria_id
     WHERE c.usuario_id = ? AND c.estado = "visible"
     ORDER BY c.fecha_creacion DESC'
);
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
$resultado = $stmt->get_result();

$contenidos = [];
while ($fila = $resultado->fetch_assoc()) {
    $fila['id'] = (int) $fila['id'];
    $fila['categoriaId'] = $fila['categoriaId'] !== null ? (int) $fila['categoriaId'] : null;
    $contenidos[] = $fila;
}
$stmt->close();

echo json_encode($contenidos);

$conexion->close();
