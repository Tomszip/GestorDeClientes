-- =====================================================================
-- schema.sql
-- Base de datos del Trabajo Final de Programacion Web 1 (CRM Gestor de Clientes)
-- Motor: MySQL / MariaDB (XAMPP - phpMyAdmin)
--
-- Como importar:
--   1. Abrir phpMyAdmin.
--   2. Pestaña "Importar" (no hace falta crear la base a mano, este script
--      la crea con CREATE DATABASE IF NOT EXISTS).
--   3. Seleccionar este archivo y ejecutar.
--
-- El script es re-importable: si la base ya existe, borra las tablas
-- anteriores (en orden inverso de dependencias) y las vuelve a crear
-- desde cero, para poder reiniciar la base durante el desarrollo.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS gestor_clientes
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestor_clientes;

-- ---------------------------------------------------------------------
-- Limpieza previa (orden inverso de dependencias, para poder re-importar)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS mensajes;
DROP TABLE IF EXISTS conversaciones;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS configuracion;
DROP TABLE IF EXISTS contenidos_compartidos;
DROP TABLE IF EXISTS contenidos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS tokens_sesion;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS temas;

-- ---------------------------------------------------------------------
-- temas
-- Parte 2 (estilos disponibles) y Parte 3 (instalador: elegir entre >=3
-- temas de diseño). No depende de ninguna otra tabla, por eso se crea
-- primero.
-- ---------------------------------------------------------------------
CREATE TABLE temas (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(50)  NOT NULL,
  archivo_css  VARCHAR(150) NOT NULL,
  activo       TINYINT(1)   NOT NULL DEFAULT 0,
  UNIQUE KEY uq_temas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- usuarios
-- Parte 1 (usuarios registrados y autenticados) y Parte 2 (rol admin).
-- tema_preferido_id es opcional: si el usuario no eligio un tema propio,
-- la aplicacion usa el tema global de la tabla "configuracion".
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(100) NOT NULL,
  email              VARCHAR(150) NOT NULL,
  contrasena         VARCHAR(255) NOT NULL,
  rol                ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  estado             ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  tema_preferido_id  INT UNSIGNED NULL,
  fecha_registro     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso      DATETIME NULL,
  UNIQUE KEY uq_usuarios_email (email),
  CONSTRAINT fk_usuarios_tema
    FOREIGN KEY (tema_preferido_id) REFERENCES temas(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- tokens_sesion
-- Autenticacion real (Parte 1) y manejo de permisos: reemplaza al
-- localStorage.token "de confianza ciega" que usa hoy el frontend.
-- Cada login genera una fila aca; cada request futura valida el token
-- contra esta tabla y su fecha de expiracion.
-- ---------------------------------------------------------------------
CREATE TABLE tokens_sesion (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT UNSIGNED NOT NULL,
  token             VARCHAR(64) NOT NULL,
  fecha_creacion    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_expiracion  DATETIME NOT NULL,
  UNIQUE KEY uq_tokens_token (token),
  KEY idx_tokens_usuario (usuario_id),
  CONSTRAINT fk_tokens_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categorias
-- Parte 2: el admin debe poder gestionar categorias de contenido.
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(80) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contenidos
-- Nucleo de la Parte 1 (subir texto, imagenes o archivos) y de la
-- Parte 2 (ABM de publicaciones: el admin puede ocultarlas o borrarlas).
-- ---------------------------------------------------------------------
CREATE TABLE contenidos (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED NOT NULL,
  categoria_id    INT UNSIGNED NULL,
  titulo          VARCHAR(150) NOT NULL,
  texto           TEXT NULL,
  tipo            ENUM('texto','imagen','archivo') NOT NULL,
  ruta_archivo    VARCHAR(255) NULL,
  fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado          ENUM('visible','oculto') NOT NULL DEFAULT 'visible',
  KEY idx_contenidos_usuario (usuario_id),
  KEY idx_contenidos_categoria (categoria_id),
  CONSTRAINT fk_contenidos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_contenidos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contenidos_compartidos
-- Tabla intermedia N:M que resuelve el requisito literal de la Parte 1:
-- "compartir estos contenidos con uno o mas usuarios registrados a los
-- cuales buscara". La UNIQUE evita compartir el mismo contenido dos
-- veces con el mismo destinatario.
-- ---------------------------------------------------------------------
CREATE TABLE contenidos_compartidos (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contenido_id      INT UNSIGNED NOT NULL,
  usuario_id        INT UNSIGNED NOT NULL,
  fecha_compartido  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  leido             TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_compartido_contenido_usuario (contenido_id, usuario_id),
  KEY idx_compartidos_usuario (usuario_id),
  CONSTRAINT fk_compartidos_contenido
    FOREIGN KEY (contenido_id) REFERENCES contenidos(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_compartidos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- configuracion
-- Parte 3 (instalador): nombre del sitio (se muestra como titulo en
-- todas las paginas) y tema activo global. Fila unica (id = 1),
-- administrable luego desde el panel sin volver a correr el instalador.
-- ---------------------------------------------------------------------
CREATE TABLE configuracion (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_sitio    VARCHAR(100) NOT NULL,
  tema_activo_id  INT UNSIGNED NOT NULL,
  instalado       TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_configuracion_tema
    FOREIGN KEY (tema_activo_id) REFERENCES temas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- clientes
-- Tematica elegida para el sitio (CRM). Reemplaza el arreglo hardcodeado
-- de ClientesComponent. Cada cliente pertenece al usuario que lo carga.
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id       INT UNSIGNED NOT NULL,
  nombre           VARCHAR(100) NOT NULL,
  email            VARCHAR(150) NOT NULL,
  telefono         VARCHAR(30) NULL,
  empresa          VARCHAR(100) NULL,
  ubicacion        VARCHAR(100) NULL,
  estado           ENUM('nuevo','activo','pendiente','inactivo') NOT NULL DEFAULT 'nuevo',
  ultimo_contacto  DATE NULL,
  valor_negocios   DECIMAL(12,2) NULL,
  fecha_creacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_clientes_usuario (usuario_id),
  CONSTRAINT fk_clientes_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- conversaciones
-- Modulo de chat con clientes (reemplaza el mock de ConversacionesComponent).
-- Distinto del "compartir contenido" de la Parte 1: aca se modela la
-- atencion al cliente propia de la tematica CRM elegida.
-- ---------------------------------------------------------------------
CREATE TABLE conversaciones (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id      INT UNSIGNED NOT NULL,
  usuario_id      INT UNSIGNED NOT NULL,
  estado          ENUM('nuevo','en-curso','cerrado') NOT NULL DEFAULT 'nuevo',
  fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_conversaciones_cliente (cliente_id),
  KEY idx_conversaciones_usuario (usuario_id),
  CONSTRAINT fk_conversaciones_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_conversaciones_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- mensajes
-- Mensajes individuales de cada conversacion.
-- ---------------------------------------------------------------------
CREATE TABLE mensajes (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversacion_id  INT UNSIGNED NOT NULL,
  texto            TEXT NOT NULL,
  es_propio        TINYINT(1) NOT NULL,
  fecha_creacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mensajes_conversacion (conversacion_id),
  CONSTRAINT fk_mensajes_conversacion
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Datos iniciales
-- Solo lo estrictamente necesario para que el sistema funcione desde
-- el primer arranque. No se crea ningun usuario administrador aca:
-- esa es responsabilidad del instalador (Parte 3), que le pedira la
-- clave de administrador al usuario final.
-- =====================================================================

-- 3 temas minimos exigidos por la consigna (Parte 3). Los archivos .css
-- reales se van a crear cuando implementemos el instalador.
INSERT INTO temas (nombre, archivo_css, activo) VALUES
  ('Claro',        'tema-claro.css',        1),
  ('Oscuro',       'tema-oscuro.css',       0),
  ('Corporativo',  'tema-corporativo.css',  0);

-- Fila unica de configuracion del sitio. "instalado" queda en 0 hasta
-- que el instalador de la Parte 3 la marque en 1 y se autodeshabilite.
INSERT INTO configuracion (nombre_sitio, tema_activo_id, instalado) VALUES
  ('CRM Pro', 1, 0);
