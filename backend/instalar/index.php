<?php
// Instalador del sitio (Parte 3 de la consigna, opcional).
// Es una pagina PHP independiente de la app Angular a proposito: tiene
// que poder ejecutarse ANTES de que exista config.php, es decir, antes
// de que el resto del backend pueda siquiera conectarse a una base.

$configPath = __DIR__ . '/../config/config.php';
$yaInstalado = file_exists($configPath);

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$yaInstalado) {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbUsuario = trim($_POST['db_usuario'] ?? '');
    $dbClave = $_POST['db_clave'] ?? '';
    $dbNombre = trim($_POST['db_nombre'] ?? '');
    $nombreSitio = trim($_POST['nombre_sitio'] ?? '');
    $temaId = (int) ($_POST['tema'] ?? 0);
    $adminNombre = trim($_POST['admin_nombre'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminClave = $_POST['admin_clave'] ?? '';

    if ($dbHost === '' || $dbUsuario === '' || $dbNombre === '') {
        $errores[] = 'Completá los datos de conexión a la base de datos (host, usuario y nombre son obligatorios).';
    }
    if ($nombreSitio === '') {
        $errores[] = 'El nombre del sitio es obligatorio.';
    }
    if (!in_array($temaId, [1, 2, 3], true)) {
        $errores[] = 'Elegí un tema para el sitio.';
    }
    if ($adminNombre === '' || $adminEmail === '' || $adminClave === '') {
        $errores[] = 'Completá el nombre, email y clave del administrador.';
    }
    if ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email del administrador no es válido.';
    }
    if ($adminClave !== '' && strlen($adminClave) < 6) {
        $errores[] = 'La clave del administrador debe tener al menos 6 caracteres.';
    }

    if (empty($errores)) {
        try {
            $conexionInstalador = new mysqli($dbHost, $dbUsuario, $dbClave, $dbNombre);
            $conexionInstalador->set_charset('utf8mb4');

            // Reutilizamos schema.sql como unica fuente de verdad, pero le
            // sacamos el CREATE DATABASE / USE: en un hosting sin acceso
            // root el usuario no tiene permiso para crear bases, la base
            // ya se la da el hosting creada de antemano.
            $sql = file_get_contents(__DIR__ . '/../config/schema.sql');
            $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
            $sql = preg_replace('/USE\s+\S+\s*;/i', '', $sql);

            $conexionInstalador->multi_query($sql);
            do {
                if ($resultado = $conexionInstalador->store_result()) {
                    $resultado->free();
                }
            } while ($conexionInstalador->more_results() && $conexionInstalador->next_result());

            // Nombre del sitio y tema elegido por el instalador.
            $stmt = $conexionInstalador->prepare(
                'UPDATE configuracion SET nombre_sitio = ?, tema_activo_id = ?, instalado = 1'
            );
            $stmt->bind_param('si', $nombreSitio, $temaId);
            $stmt->execute();
            $stmt->close();

            $conexionInstalador->query('UPDATE temas SET activo = 0');
            $stmt = $conexionInstalador->prepare('UPDATE temas SET activo = 1 WHERE id = ?');
            $stmt->bind_param('i', $temaId);
            $stmt->execute();
            $stmt->close();

            // Usuario administrador inicial.
            $hash = password_hash($adminClave, PASSWORD_DEFAULT);
            $stmt = $conexionInstalador->prepare(
                'INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (?, ?, ?, "admin")'
            );
            $stmt->bind_param('sss', $adminNombre, $adminEmail, $hash);
            $stmt->execute();
            $stmt->close();

            $conexionInstalador->close();

            // Archivo de configuracion general. Su sola existencia es lo
            // que anula que el instalador pueda volver a correr.
            $contenidoConfig = "<?php\n"
                . "// Generado por el instalador. No editar a mano salvo que sepas lo que haces.\n"
                . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                . "define('DB_USUARIO', " . var_export($dbUsuario, true) . ");\n"
                . "define('DB_CLAVE', " . var_export($dbClave, true) . ");\n"
                . "define('DB_NOMBRE', " . var_export($dbNombre, true) . ");\n";

            file_put_contents($configPath, $contenidoConfig);

            $exito = true;
        } catch (mysqli_sql_exception $e) {
            $errores[] = 'Error al conectar o configurar la base de datos: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Instalación del sitio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #F8FAFC;
      color: #0F172A;
      margin: 0;
      padding: 40px 16px;
    }
    .contenedor { max-width: 480px; margin: 0 auto; }
    .logo {
      width: 56px; height: 56px; background: #0F172A; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      color: white; font-weight: bold; font-size: 22px; margin: 0 auto 16px;
    }
    h1 { text-align: center; font-size: 22px; margin-bottom: 4px; }
    p.subtitulo { text-align: center; color: #64748B; margin-top: 0; margin-bottom: 24px; }
    .card { background: white; border: 1px solid #E2E8F0; border-radius: 12px; padding: 28px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    fieldset { border: none; padding: 0; margin: 0 0 20px; }
    legend { font-weight: 600; font-size: 14px; margin-bottom: 10px; padding: 0; }
    label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; margin-top: 12px; }
    input[type=text], input[type=email], input[type=password] {
      width: 100%; padding: 8px 10px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px;
    }
    .temas { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
    .tema-opcion { display: flex; align-items: center; gap: 10px; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px; }
    button {
      width: 100%; background: #0F172A; color: white; border: none; border-radius: 8px;
      padding: 10px; font-size: 15px; font-weight: 500; cursor: pointer; margin-top: 8px;
    }
    button:hover { background: #1E293B; }
    .alerta { border-radius: 8px; padding: 12px 14px; font-size: 14px; margin-bottom: 16px; }
    .alerta-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FEE2E2; }
    .alerta-exito { background: #F0FDF4; color: #166534; border: 1px solid #DCFCE7; }
    .nota { font-size: 12px; color: #94A3B8; margin-top: 4px; }
  </style>
</head>
<body>
  <div class="contenedor">
    <div class="logo">C</div>
    <h1>Instalación del sitio</h1>
    <p class="subtitulo">Configuración inicial — se hace una sola vez</p>

    <div class="card">
      <?php if ($yaInstalado && !$exito): ?>
        <div class="alerta alerta-error">
          El sitio ya fue instalado. Si necesitás reinstalarlo, un administrador tiene que borrar manualmente
          <code>backend/config/config.php</code> primero (esto no se puede hacer desde acá, a propósito).
        </div>
      <?php elseif ($exito): ?>
        <div class="alerta alerta-exito">
          Instalación completada. Ya podés iniciar sesión con el usuario administrador que creaste.
        </div>
      <?php else: ?>
        <?php foreach ($errores as $error): ?>
          <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="post">
          <fieldset>
            <legend>Base de datos (datos que te dio tu hosting)</legend>
            <label>Host de la base de datos</label>
            <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>

            <label>Usuario de la base de datos</label>
            <input type="text" name="db_usuario" value="<?= htmlspecialchars($_POST['db_usuario'] ?? '') ?>" required>

            <label>Clave de la base de datos</label>
            <input type="password" name="db_clave">

            <label>Nombre de la base de datos</label>
            <input type="text" name="db_nombre" value="<?= htmlspecialchars($_POST['db_nombre'] ?? '') ?>" required>
          </fieldset>

          <fieldset>
            <legend>Sitio</legend>
            <label>Nombre del sitio</label>
            <input type="text" name="nombre_sitio" value="<?= htmlspecialchars($_POST['nombre_sitio'] ?? 'CRM Pro') ?>" required>
            <p class="nota">Se muestra como título en todas las páginas.</p>

            <label>Tema visual</label>
            <div class="temas">
              <label class="tema-opcion"><input type="radio" name="tema" value="1" checked> Claro</label>
              <label class="tema-opcion"><input type="radio" name="tema" value="2"> Oscuro</label>
              <label class="tema-opcion"><input type="radio" name="tema" value="3"> Corporativo</label>
            </div>
          </fieldset>

          <fieldset>
            <legend>Usuario administrador</legend>
            <label>Nombre completo</label>
            <input type="text" name="admin_nombre" value="<?= htmlspecialchars($_POST['admin_nombre'] ?? '') ?>" required>

            <label>Email</label>
            <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>

            <label>Clave</label>
            <input type="password" name="admin_clave" required>
            <p class="nota">Mínimo 6 caracteres.</p>
          </fieldset>

          <button type="submit">Instalar</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
