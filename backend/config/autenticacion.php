<?php
// Lee el header "Authorization: Bearer <token>" de la petición, sea cual
// sea la forma en que PHP/Apache lo expongan (varía segun configuracion).
function obtenerTokenDesdeHeader(): ?string
{
    $encabezados = [];

    if (function_exists('getallheaders')) {
        $encabezados = getallheaders();
    } elseif (function_exists('apache_request_headers')) {
        $encabezados = apache_request_headers();
    }

    $autorizacion = null;
    foreach ($encabezados as $nombre => $valor) {
        if (strtolower($nombre) === 'authorization') {
            $autorizacion = $valor;
            break;
        }
    }

    if ($autorizacion === null && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $autorizacion = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if ($autorizacion === null) {
        return null;
    }

    if (preg_match('/Bearer\s+(\S+)/i', $autorizacion, $coincidencias)) {
        return $coincidencias[1];
    }

    return null;
}

// Valida el token contra tokens_sesion y devuelve los datos del usuario
// autenticado. Si no hay token, es invalido o esta vencido, corta la
// ejecucion con 401 (por eso todos los endpoints protegidos pueden
// llamarla sin chequear el resultado: si vuelve, es porque es valido).
function obtenerUsuarioAutenticado(mysqli $conexion): array
{
    $token = obtenerTokenDesdeHeader();

    if ($token === null) {
        http_response_code(401);
        echo json_encode(['mensaje' => 'No se envió un token de autenticación.']);
        exit;
    }

    $stmt = $conexion->prepare(
        'SELECT u.id, u.nombre, u.email, u.rol, u.estado
         FROM tokens_sesion t
         INNER JOIN usuarios u ON u.id = t.usuario_id
         WHERE t.token = ? AND t.fecha_expiracion > NOW()'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['mensaje' => 'Token inválido o expirado.']);
        exit;
    }

    return $usuario;
}

// Corta la ejecucion con 403 si el usuario autenticado no es admin. La
// usan los endpoints que la consigna reserva al administrador (ABM de
// usuarios, categorias, estilos, moderacion de contenidos).
function requerirAdmin(array $usuario): void
{
    if ($usuario['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['mensaje' => 'No tenés permisos de administrador para esta acción.']);
        exit;
    }
}
