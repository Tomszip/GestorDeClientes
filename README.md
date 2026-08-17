# CRM Pro — Gestor de Clientes

## Carátula

- **Autores:** Lucero, Ignacio Tomás — Acosta, Sofía
- **Materia:** Programación Web 1
- **Institución:** UCES – Tecnicatura en Programación de Sistemas
- **Fecha de presentación:** 22/07/2026

---

## Índice

1. [Presentación del sistema](#1-presentación-del-sistema)
2. [Arquitectura y tecnologías](#2-arquitectura-y-tecnologías)
3. [Modelo de datos](#3-modelo-de-datos)
4. [Funcionalidades implementadas](#4-funcionalidades-implementadas)
5. [Bibliotecas de terceros utilizadas](#5-bibliotecas-de-terceros-utilizadas)
6. [Manual de instalación](#6-manual-de-instalación)
7. [Manual de uso](#7-manual-de-uso)
8. [Capturas de pantalla](#8-capturas-de-pantalla)
9. [Estructura del proyecto](#9-estructura-del-proyecto)
10. [Problemas conocidos y decisiones de alcance](#10-problemas-conocidos-y-decisiones-de-alcance)

---

## 1. Presentación del sistema

**CRM Pro** es un sistema de gestión de relación con clientes (CRM) desarrollado como trabajo final de Programación Web 1. Permite a usuarios registrados administrar una cartera de clientes, llevar un registro de conversaciones con ellos, y subir y compartir contenido (texto, imágenes y archivos) con otros usuarios del sistema. Un usuario con rol de administrador puede además gestionar las cuentas de usuario, las categorías de contenido, moderar las publicaciones de todos los usuarios, y configurar el nombre y el tema visual del sitio.

El proyecto está dividido en dos partes independientes que se comunican por HTTP/JSON:

- **Frontend**: aplicación Angular (SPA) que consume la API.
- **Backend**: API REST en PHP nativo (sin frameworks) sobre MySQL, pensada para poder instalarse en un hosting básico sin acceso root.

### Por qué esta temática

Se eligió un CRM porque permite cubrir de forma natural los tres pilares que pide la consigna: usuarios que suben y comparten contenido (Parte 1), un panel de administración con ABM de usuarios/categorías/moderación (Parte 2), y un instalador que configura el sitio en un hosting real (Parte 3), todo dentro de un dominio de aplicación coherente (gestión de clientes y su seguimiento).

---

## 2. Arquitectura y tecnologías

```
┌─────────────────────┐         HTTP / JSON         ┌──────────────────────┐
│   Frontend (Angular) │ ───────────────────────────>│   Backend (PHP)      │
│   localhost:4200     │ <───────────────────────────│   localhost/backend  │
│   (ng serve)          │                             │   (Apache/XAMPP)     │
└─────────────────────┘                              └──────────┬───────────┘
                                                                  │
                                                                  │ mysqli
                                                                  ▼
                                                        ┌──────────────────┐
                                                        │   MySQL/MariaDB   │
                                                        │  gestor_clientes  │
                                                        └──────────────────┘
```

### Frontend

- **Angular** (NgModules clásicos, sin standalone components) + TypeScript.
- **Bootstrap 5.3** y **Bootstrap Icons** para la interfaz.
- Arquitectura por capas: `pages/` (pantallas), `components/` (reutilizables: sidebar, navbar), `services/` (acceso a la API), `models/` (interfaces TypeScript), `guards/` (protección de rutas).
- Un servicio por entidad (`ClienteService`, `UsuarioService`, `ContenidoService`, `ConversacionService`, `CategoriaService`, `ConfiguracionService`) más un `AuthService` central que maneja el token de sesión.

### Backend

- **PHP nativo** (sin Laravel, Symfony ni ningún framework), organizado por carpetas según el recurso que exponen (`auth/`, `clientes/`, `usuarios/`, `contenidos/`, `conversaciones/`, `categorias/`, `config/`).
- **MySQL/MariaDB** vía la extensión `mysqli`, con sentencias preparadas en todas las consultas (prevención de inyección SQL).
- Autenticación por **token** (no sesiones de PHP): cada login genera un token aleatorio guardado en la tabla `tokens_sesion`, que se envía en el header `Authorization: Bearer <token>` en cada pedido protegido.
- Contraseñas guardadas con `password_hash()` (bcrypt), nunca en texto plano.

---

## 3. Modelo de datos

10 tablas, todas con claves foráneas e integridad referencial:

```
usuarios ──┬── tokens_sesion
           ├── contenidos ──── contenidos_compartidos ──── usuarios (destinatario)
           │        └──── categorias
           ├── clientes ──── conversaciones ──── mensajes
           └── (rol=admin gestiona: categorias, temas, configuracion, moderación de contenidos)

temas ──── configuracion
```

| Tabla | Para qué sirve |
|---|---|
| `usuarios` | Cuentas del sistema (rol admin/usuario, estado activo/inactivo) |
| `tokens_sesion` | Tokens de autenticación, con expiración |
| `categorias` | Categorías de contenido (gestionadas por el admin) |
| `contenidos` | Publicaciones de texto/imagen/archivo de cada usuario |
| `contenidos_compartidos` | Relación N:M — qué contenido se compartió con qué usuarios |
| `temas` | Los 3 temas visuales disponibles (Claro/Oscuro/Corporativo) |
| `configuracion` | Nombre del sitio y tema activo (fila única) |
| `clientes` | Cartera de clientes de cada usuario (temática CRM) |
| `conversaciones` | Registro de interacciones con cada cliente |
| `mensajes` | Mensajes individuales de cada conversación |

El script completo para crear la base está en [`backend/config/schema.sql`](backend/config/schema.sql), con comentarios explicando el porqué de cada tabla y cada restricción.

---

## 4. Funcionalidades implementadas

### Parte 1 — Usuarios registrados y contenido compartido

- Registro e inicio de sesión con contraseña hasheada y token de sesión.
- Subida de contenido propio: texto, imágenes (jpg/png/gif/webp) o archivos (pdf/doc/docx/xls/xlsx/zip), hasta 10 MB.
- Búsqueda de usuarios registrados y compartición de contenido con uno o más de ellos.
- Sección "Compartido conmigo" para ver lo que otros usuarios compartieron.

### Parte 2 — Panel de administración

- ABM completo de usuarios (alta, baja, edición de rol/estado), restringido a administradores tanto en la interfaz como en el backend.
- Gestión de categorías de contenido (CRUD).
- Moderación de contenidos: el administrador puede ver las publicaciones de **todos** los usuarios, ocultarlas/mostrarlas sin borrarlas, o eliminarlas.
- Reglas de seguridad adicionales: un administrador no puede eliminar ni desactivar su propia cuenta ni quitarse el rol de admin (para que el sistema nunca quede sin ningún administrador activo).

### Parte 3 — Instalador y temas (opcional)

- Instalador web (`backend/instalar/`) que pide los datos de conexión a la base, nombre del sitio, tema inicial y datos del administrador; crea el esquema completo, el usuario admin, y un archivo de configuración (`backend/config/config.php`). Se autodeshabilita una vez instalado.
- 3 temas visuales reales e intercambiables (Claro, Oscuro, Corporativo), seleccionables por el admin desde el panel sin volver a instalar nada.
- El nombre del sitio configurado se muestra dinámicamente como título en todas las páginas.

---

## 5. Bibliotecas de terceros utilizadas

Se documentan explícitamente, tal como exige la consigna:

| Biblioteca | Uso | Licencia | Fuente |
|---|---|---|---|
| [Angular](https://angular.dev/) | Framework del frontend | MIT | angular.dev |
| [Bootstrap 5.3](https://getbootstrap.com/) | Estilos y componentes de UI | MIT | getbootstrap.com |
| [Bootstrap Icons](https://icons.getbootstrap.com/) | Iconografía | MIT | icons.getbootstrap.com |
| [RxJS](https://rxjs.dev/) | Programación reactiva (incluido con Angular) | MIT | rxjs.dev |
| [Zone.js](https://github.com/angular/angular/tree/main/packages/zone.js) | Detección de cambios automática de Angular | MIT | github.com/angular |
| [Google Fonts — Inter](https://fonts.google.com/specimen/Inter) | Tipografía | Open Font License | fonts.google.com |

No se utilizó ningún framework de backend (PHP nativo puro) ni ninguna biblioteca de JavaScript adicional fuera de las provistas por el propio Angular CLI.

---

## 6. Manual de instalación

### Requisitos previos

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL/MariaDB + PHP 8.1 o superior)
- [Node.js](https://nodejs.org/) y npm

### Opción A — Instalación con el instalador web (recomendada)

1. Copiar la carpeta completa del proyecto dentro de `htdocs` de XAMPP.
2. Iniciar **Apache** y **MySQL** desde el Panel de Control de XAMPP.
3. Entrar a phpMyAdmin y crear una base de datos **vacía** (sin importar ningún script), con el nombre que se prefiera.
4. Ir a `http://localhost/<carpeta-del-proyecto>/backend/instalar/` y completar el formulario:
   - Datos de conexión a la base creada en el paso 3.
   - Nombre del sitio y tema visual inicial.
   - Nombre, email y contraseña del usuario administrador.
5. Instalar dependencias del frontend y levantarlo:
   ```bash
   cd frontend
   npm install
   npm start
   ```
6. Entrar a `http://localhost:4200` e iniciar sesión con el usuario administrador creado en el paso 4.

### Opción B — Importar el esquema directamente (para desarrollo)

1. Pasos 1 y 2 iguales a la Opción A.
2. En phpMyAdmin, importar directamente [`backend/config/schema.sql`](backend/config/schema.sql) (crea la base y las tablas, con 3 temas precargados pero sin usuario administrador).
3. Crear manualmente `backend/config/config.php` con las credenciales de la base:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_USUARIO', 'root');
   define('DB_CLAVE', '');
   define('DB_NOMBRE', 'nombre_de_la_base');
   ```
4. Registrar un usuario desde `/registro` y promoverlo a `admin` manualmente en la tabla `usuarios` (columna `rol`), o directamente por SQL.
5. Levantar el frontend igual que en la Opción A (pasos 5 y 6).

> **Nota:** si `backend/config/config.php` no existe, cualquier pedido a la API devuelve `503` indicando que el sitio no está instalado — es intencional, no un error.

---

## 7. Manual de uso

- **Login / Registro**: cualquier persona puede crear una cuenta desde `/registro`. Los usuarios nuevos se crean con rol `usuario`.
- **Dashboard**: métricas generales de uso.
- **Conversaciones**: elegí "Nueva conversación" para iniciar un registro de interacción con uno de tus clientes; los mensajes que cargás quedan asociados a esa conversación, que podés marcar "en curso" o "cerrada".
- **Clientes**: alta, edición y baja de tu cartera de clientes (nombre, email, teléfono y empresa son obligatorios; ubicación es opcional).
- **Mis Contenidos**: subí texto, imágenes o archivos, asigná una categoría (opcional), y compartilos con otros usuarios buscándolos por nombre o email.
- **Usuarios / Categorías / Moderación / Configuración** (solo visibles para administradores): ABM de cuentas, gestión de categorías, moderación de publicaciones de todos los usuarios, y configuración del nombre/tema del sitio.

---

## 8. Capturas de pantalla

> _Espacio reservado — agregar antes de entregar el trabajo:_
> - Pantalla de login
> - Dashboard
> - Mis Contenidos (con el panel de "compartir" abierto)
> - Panel de Usuarios (vista de administrador)
> - Configuración del sitio, mostrando los 3 temas
> - El sitio en modo Oscuro y en modo Corporativo, para mostrar el cambio de tema en vivo

---

## 9. Estructura del proyecto

```
GestorDeClientes/
├── backend/
│   ├── auth/            → login.php, registro.php
│   ├── categorias/       → CRUD de categorías (admin)
│   ├── clientes/         → CRUD de clientes
│   ├── config/            → conexion.php, config.php (generado), schema.sql, configuración del sitio
│   ├── contenidos/       → subir/listar/compartir/moderar contenido
│   ├── conversaciones/   → conversaciones y mensajes
│   ├── instalar/         → instalador web (Parte 3)
│   ├── usuarios/          → CRUD de usuarios y búsqueda (admin)
│   └── uploads/           → archivos subidos por los usuarios
└── frontend/
    └── src/app/
        ├── components/    → sidebar, navbar
        ├── guards/        → protección de rutas autenticadas
        ├── layout/        → estructura general (sidebar + navbar + contenido)
        ├── models/        → interfaces TypeScript
        ├── pages/         → una carpeta por pantalla
        └── services/      → un servicio por entidad, hablan con el backend
```

---

## 10. Problemas conocidos y decisiones de alcance

Documentado con honestidad, tal como pide la consigna ("no se trata de rellenar... sino de destacar lo que consideren importante"):

- **Conversaciones es de un solo lado**: como los "clientes" son fichas de contacto y no usuarios del sistema (no tienen cuenta propia), el módulo de Conversaciones funciona como un registro de las interacciones del usuario con su cliente, no como un chat bidireccional en tiempo real.
- **Etiquetas e historial de interacciones**: estaban contempladas en el diseño visual original pero no se implementaron, porque no forman parte de ningún requisito explícito de la consigna y hubieran requerido tablas adicionales no planificadas.
- **Cambio de tema**: al guardar un cambio de tema desde "Configuración", la página se recarga automáticamente — es necesario porque el navegador no siempre repinta al instante los estilos `!important` de una hoja de estilos cambiada dinámicamente.
- **`zone.js`**: el proyecto se generó originalmente sin esta dependencia (el andamiaje inicial usaba un enfoque "zoneless"); se agregó durante el desarrollo porque el resto del código está escrito con el patrón clásico de Angular (asignación directa de propiedades + binding en el HTML), que depende de `zone.js` para actualizar la pantalla automáticamente tras una respuesta HTTP.
