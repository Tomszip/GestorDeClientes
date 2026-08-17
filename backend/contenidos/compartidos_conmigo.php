<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = obtenerUsuarioAutenticado($conexion);

$stmt = $conexion->prepare(
    'SELECT c.id, c.titulo, c.texto, c.tipo,
            c.ruta_archivo AS rutaArchivo,
            c.fecha_creacion AS fechaCreacion,
            u.nombre AS compartidoPor,
            cc.fecha_compartido AS fechaCompartido
     FROM contenidos_compartidos cc
     INNER JOIN contenidos c ON c.id = cc.contenido_id
     INNER JOIN usuarios u ON u.id = c.usuario_id
     WHERE cc.usuario_id = ? AND c.estado = "visible"
     ORDER BY cc.fecha_compartido DESC'
);
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
$resultado = $stmt->get_result();

$contenidos = [];
while ($fila = $resultado->fetch_assoc()) {
    $fila['id'] = (int) $fila['id'];
    $contenidos[] = $fila;
}
$stmt->close();

echo json_encode($contenidos);

$conexion->close();
