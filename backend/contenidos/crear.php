<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$usuario = obtenerUsuarioAutenticado($conexion);

$titulo = trim($_POST['titulo'] ?? '');
$texto = trim($_POST['texto'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$categoriaId = (int) ($_POST['categoriaId'] ?? 0);

$tiposValidos = ['texto', 'imagen', 'archivo'];
if ($titulo === '' || !in_array($tipo, $tiposValidos, true)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos obligatorios o el tipo no es válido.']);
    exit;
}

if ($tipo === 'texto' && $texto === '') {
    http_response_code(400);
    echo json_encode(['mensaje' => 'El contenido de texto no puede estar vacío.']);
    exit;
}

$rutaArchivo = null;

if ($tipo === 'imagen' || $tipo === 'archivo') {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['mensaje' => 'Tenés que adjuntar un archivo.']);
        exit;
    }

    $archivo = $_FILES['archivo'];
    $tamanoMaximo = 10 * 1024 * 1024; // 10 MB (una foto de celular puede pesar varios MB)

    if ($archivo['size'] > $tamanoMaximo) {
        http_response_code(400);
        echo json_encode(['mensaje' => 'El archivo supera el tamaño máximo permitido (10 MB).']);
        exit;
    }

    $extensionesImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extensionesArchivo = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'];
    $extensionesPermitidas = $tipo === 'imagen' ? $extensionesImagen : $extensionesArchivo;

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas, true)) {
        http_response_code(400);
        echo json_encode(['mensaje' => 'Extensión de archivo no permitida.']);
        exit;
    }

    // Para "imagen" chequeamos que el contenido sea realmente una imagen
    // valida, no solo que tenga una extension permitida (un .jpg podria
    // ser cualquier otra cosa con el nombre cambiado).
    if ($tipo === 'imagen' && @getimagesize($archivo['tmp_name']) === false) {
        http_response_code(400);
        echo json_encode(['mensaje' => 'El archivo no es una imagen válida.']);
        exit;
    }

    // Nombre unico para que dos usuarios no se pisen un archivo con el
    // mismo nombre original.
    $nombreArchivo = uniqid('contenido_', true) . '.' . $extension;
    $rutaDestino = __DIR__ . '/../uploads/' . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        http_response_code(500);
        echo json_encode(['mensaje' => 'No se pudo guardar el archivo.']);
        exit;
    }

    $rutaArchivo = $nombreArchivo;
}

$textoParaGuardar = $texto === '' ? null : $texto;

// La categoria es opcional. Si mandan un id que no existe, lo tratamos
// como "sin categoria" en vez de dejar que la restriccion de clave
// foranea tire un error feo.
$categoriaIdParaGuardar = null;
if ($categoriaId > 0) {
    $stmt = $conexion->prepare('SELECT id FROM categorias WHERE id = ?');
    $stmt->bind_param('i', $categoriaId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $categoriaIdParaGuardar = $categoriaId;
    }
    $stmt->close();
}

$stmt = $conexion->prepare(
    'INSERT INTO contenidos (usuario_id, titulo, texto, tipo, ruta_archivo, categoria_id) VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('issssi', $usuario['id'], $titulo, $textoParaGuardar, $tipo, $rutaArchivo, $categoriaIdParaGuardar);
$stmt->execute();
$contenidoId = $stmt->insert_id;
$stmt->close();

$nombreCategoria = null;
if ($categoriaIdParaGuardar !== null) {
    $stmt = $conexion->prepare('SELECT nombre FROM categorias WHERE id = ?');
    $stmt->bind_param('i', $categoriaIdParaGuardar);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $nombreCategoria = $fila ? $fila['nombre'] : null;
    $stmt->close();
}

echo json_encode([
    'id' => $contenidoId,
    'titulo' => $titulo,
    'texto' => $textoParaGuardar,
    'tipo' => $tipo,
    'rutaArchivo' => $rutaArchivo,
    'fechaCreacion' => date('Y-m-d H:i:s'),
    'categoriaId' => $categoriaIdParaGuardar,
    'categoriaNombre' => $nombreCategoria,
]);

$conexion->close();
