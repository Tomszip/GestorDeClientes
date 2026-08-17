<?php
// El php.ini de XAMPP trae "Europe/Berlin" por defecto. Sin esto, las
// fechas que arma PHP (por ejemplo, la hora del mensaje que se devuelve
// al instante en enviar_mensaje.php) no coinciden con las que guarda
// MySQL con NOW()/CURRENT_TIMESTAMP, que sí usan la hora real del
// sistema. Hay que fijarla explícitamente para que ambos coincidan.
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Cabeceras CORS: el frontend Angular corre en localhost:4200 y este
// backend en localhost (Apache), son "origenes" distintos para el
// navegador. Sin esto, el navegador bloquea las respuestas.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// El navegador manda un pedido OPTIONS de "preflight" antes del POST
// real cuando el body es JSON. Hay que responderlo vacio y cortar.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Las credenciales de la base ya no estan hardcodeadas: las genera el
// instalador (Parte 3) en config.php la primera vez que se corre. Si
// ese archivo no existe todavia, el sitio no esta instalado.
$archivoConfig = __DIR__ . '/config.php';

if (!file_exists($archivoConfig)) {
    http_response_code(503);
    echo json_encode(['mensaje' => 'El sitio todavía no está instalado. Ejecutá el instalador (backend/instalar/) primero.']);
    exit;
}

require_once $archivoConfig;

$host = DB_HOST;
$usuarioDb = DB_USUARIO;
$claveDb = DB_CLAVE;
$nombreBaseDatos = DB_NOMBRE;

// Desde PHP 8.1, mysqli lanza una excepcion (mysqli_sql_exception) si la
// conexion falla, en vez de devolver false silenciosamente como antes.
// Por eso hace falta el try/catch: sin esto, un corte de MySQL termina
// mostrando el stack trace completo de PHP (con rutas del servidor) en
// vez de un JSON de error prolijo.
try {
    $conexion = new mysqli($host, $usuarioDb, $claveDb, $nombreBaseDatos);
    $conexion->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['mensaje' => 'Error de conexión a la base de datos.']);
    exit;
}
