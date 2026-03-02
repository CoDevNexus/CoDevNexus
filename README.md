# CoDevNexus — SPA Portfolio MVC

> PHP 8 puro · MVC sin frameworks · SPA con fetch API · Dark Industrial · Panel admin completo

---

## Tabla de Contenidos

1. [Descripción](#descripción)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Requisitos](#requisitos)
4. [Instalación rápida](#instalación-rápida)
5. [Configuración](#configuración)
6. [Estructura del Proyecto](#estructura-del-proyecto)
7. [Base de Datos](#base-de-datos)
8. [Panel Admin](#panel-admin)
9. [API Endpoints](#api-endpoints)
10. [Subida de Imágenes](#subida-de-imágenes)
11. [Notificaciones Telegram](#notificaciones-telegram)
12. [Email SMTP](#email-smtp)
13. [Seguridad](#seguridad)
14. [Personalización del Tema](#personalización-del-tema)
15. [Modo Mantenimiento y Modo Seguro](#modo-mantenimiento-y-modo-seguro)
16. [CDNs utilizados](#cdns-utilizados)
17. [Flujo de Verificación](#flujo-de-verificación)

---

## Descripción

**CoDevNexus** es un portfolio web personal tipo SPA (Single Page Application) construido con **PHP 8 puro**, sin Composer ni frameworks externos. Toda la configuración — colores, logos, redes sociales, SMTP, Telegram, modo seguro — se gestiona desde el panel admin y se almacena en base de datos. El frontend nunca tiene valores hardcodeados.

### Características principales

- SPA dinámica: el JS hace fetch a los endpoints PHP y renderiza el contenido
- Tema Dark Industrial completamente personalizable desde el admin
- Panel admin con CRUD completo para secciones, portafolio, tecnologías y mensajes
- Upload dual por imagen: **Local** (servidor) o **ImgBB** (CDN externo), elegido por toggle
- Selector de iconos: **Devicons** (lista de 200+) o **SVG personalizado** sanitizado
- Redes sociales dinámicas desde BD — solo se muestran las que tienen valor
- Notificaciones **Telegram** en tiempo real para contactos, login fallido, etc.
- Servidor SMTP configurable en caliente desde el admin (sin reiniciar nada)
- **Modo Seguro**: oculta proyectos/secciones marcados sin eliminarlos
- **Modo Mantenimiento**: muestra página 503 al público, el admin sigue navegando
- Seguridad completa: CSRF, PDO preparado, XSS, bcrypt, rate limiting por IP

---

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8+ (puro, sin frameworks) |
| Base de datos | MySQL 8 / MariaDB 10+ via PDO |
| Frontend | Vanilla JS (ES6+), Fetch API |
| Animaciones | AOS.js, Particles.js |
| Iconos | Devicons (CDN) |
| Editor rico | Quill.js (solo admin) |
| Servidor local | Laragon (Apache + PHP 8) |

---

## Requisitos

- **PHP 8.0+** con extensiones: `pdo_mysql`, `openssl`, `fileinfo`, `dom`
- **MySQL 8** o **MariaDB 10.4+**
- Servidor web con soporte `mod_rewrite` (Apache) — Laragon lo incluye
- Acceso a internet para cargar CDNs (AOS, Particles, Devicons, Quill)

---

## Instalación rápida

### 1. Clonar / copiar el proyecto

```
d:\laragon\www\codevnexus\
```

### 2. Configurar el virtual host (Laragon)

- Click derecho en Laragon → **Apache → sites-enabled → Add site** (nombre: `codevnexus.test`, carpeta: `...\codevnexus\public`)
- O editar `C:\laragon\etc\apache2\sites-enabled\auto.codevnexus.test.conf`:

```apache
<VirtualHost *:80>
    ServerName codevnexus.test
    DocumentRoot "D:/laragon/www/codevnexus/public"
    <Directory "D:/laragon/www/codevnexus/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Reiniciar Apache en Laragon.

### 3. Ejecutar el instalador web

Abre en el navegador:

```
http://codevnexus.test/install.php
```

El instalador te guiará paso a paso para:
- Conectar con la base de datos (la crea si no existe)
- Crear las tablas (schema completo)
- Configurar el nombre del sitio, email y URL
- Crear el **usuario administrador** con la contraseña que elijas
- Generar automáticamente `config/config.php` con `APP_KEY` único

> **Seguridad:** el instalador genera `config/config.php` con una clave única aleatoria.  
> **Elimina o protege `public/install.php`** una vez completada la instalación.

### 4. Primer acceso al admin

```
http://codevnexus.test/admin/login
```

Usa el usuario y contraseña que elegiste en el instalador.

### Alternativa: instalación manual

Si prefieres no usar el instalador web:

```bash
# 1. Crear BD e importar schema
mysql -u root -p -e "CREATE DATABASE codevnexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p codevnexus < database/schema.sql

# 2. Copiar y editar config
cp config/config.example.php config/config.php
# Editar config/config.php con tus credenciales

# 3. Crear usuario admin por consola (cambia los valores)
php -r "echo password_hash('tu_password', PASSWORD_BCRYPT);"
mysql -u root -p codevnexus -e "INSERT INTO admin_users (username,password) VALUES ('admin','HASH_AQUI');"
```

> **Cambiar la contraseña inmediatamente** desde Admin → Configuración → Pestaña 7 (Seguridad)

---

## Configuración

Todo se gestiona desde `/admin/configuracion` — **nunca edites el código** para cambiar datos del sitio.

### `config/config.php` — Lo único que va aquí

```
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
APP_KEY   → clave para AES-256 (mínimo 32 caracteres)
APP_ENV   → 'development' | 'production'
APP_URL   → URL base sin barra final
```

### Todo lo demás → tabla `configuracion` en la BD

La tabla usa el patrón **clave → valor**. Se edita desde el panel admin. Grupos de claves:

| Prefijo | Contenido |
|---|---|
| `site_*` | Nombre, tagline, email, teléfono, footer |
| `logo_*` | Rutas/URLs de logos y favicon |
| `theme_*` | Colores, partículas, glow |
| `smtp_*` | Host, puerto, cifrado, usuario, contraseña |
| `telegram_*` | Bot token, chat ID, toggles de notificaciones |
| `imgbb_*` | API key de ImgBB |
| `recaptcha_*` | Site key y secret de reCAPTCHA v3 |
| `social_*` | URLs/handles de redes sociales |
| `modo_*` | Modo seguro, modo mantenimiento |

---

## Estructura del Proyecto

```
codevnexus/
├── public/                    ← DocumentRoot (único directorio expuesto)
│   ├── index.php              ← Front Controller
│   ├── .htaccess              ← Rewrite + headers seguridad
│   ├── uploads/
│   │   ├── .htaccess          ← Bloquea ejecución PHP en uploads
│   │   ├── portafolio/
│   │   ├── tecnologias/
│   │   └── branding/          ← Logos y favicon subidos
│   └── assets/
│       ├── css/
│       │   ├── style.css      ← Tema Dark Industrial (variables CSS)
│       │   └── admin.css      ← Panel admin
│       ├── js/
│       │   ├── app.js         ← SPA boot + render + contacto
│       │   └── particles-config.js
│       └── img/
│           └── logo.svg
│
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── Api/               ← Endpoints JSON públicos
│   │   └── Admin/             ← CRUD + configuración
│   ├── Models/
│   ├── Views/
│   │   ├── layouts/           ← main.php, admin.php
│   │   ├── home/
│   │   └── admin/
│   └── Middleware/
│       ├── AuthMiddleware.php
│       └── CsrfMiddleware.php
│
├── core/                      ← Motor MVC
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   ├── Request.php
│   ├── Response.php
│   ├── Database.php           ← Singleton PDO
│   ├── Security.php           ← CSRF, sanitize, AES, rateLimit
│   ├── ImageUploader.php      ← Estrategia Local / ImgBB
│   ├── Mailer.php             ← SMTP manual + fallback mail()
│   └── Telegram.php           ← Bot API curl
│
├── config/
│   ├── config.php             ← Solo DB + APP_KEY
│   ├── routes.php             ← Todas las rutas
│   └── session.php            ← Sesión segura
│
├── database/
│   └── schema.sql             ← Esquema + datos semilla
│
└── .gitignore
```

---

## Base de Datos

### Importar esquema

```bash
mysql -u root -p codevnexus < database/schema.sql
```

### Tablas

| Tabla | Descripción |
|---|---|
| `admin_users` | Usuarios del panel (bcrypt) |
| `secciones` | Bloques del SPA: hero, sobre, portafolio, contacto, etc. |
| `tecnologias` | Stack con nivel (%), icono Devicons o SVG |
| `portafolio` | Proyectos con imagen local o ImgBB |
| `mensajes` | Bandeja de contacto del formulario público |
| `configuracion` | Clave-valor: tema, SMTP, Telegram, sociales, logos |
| `login_attempts` | Rate limiting de intentos de login por IP |

### Usuario admin por defecto (semilla)

```
username: admin
password: admin123
```

Hash generado con `password_hash('admin123', PASSWORD_BCRYPT)`.

---

## Panel Admin

### Acceso

```
http://codevnexus.test/admin/login
```

### Secciones disponibles

| Ruta | Descripción |
|---|---|
| `/admin` | Dashboard con métricas y toggles rápidos |
| `/admin/secciones` | CRUD de bloques del SPA (con Quill.js) |
| `/admin/portafolio` | CRUD de proyectos (upload dual Local/ImgBB) |
| `/admin/tecnologias` | CRUD del stack (Devicons o SVG custom) |
| `/admin/mensajes` | Bandeja de mensajes recibidos desde el formulario |
| `/admin/configuracion` | 7 pestañas de configuración centralizada |

### Pestañas de Configuración

| # | Pestaña | Contenido |
|---|---|---|
| 1 | Empresa & Marca | Nombre, tagline, email, teléfono, dirección, footer |
| 2 | Logos & Favicon | Subir/cambiar logo principal, logo admin, favicon |
| 3 | Colores & Tema | Paleta de colores, partículas, intensidad del glow |
| 4 | Servidor de Email | SMTP host/puerto/cifrado/usuario/contraseña + botón de prueba |
| 5 | APIs Externas | Telegram (token + chat ID), ImgBB (API key), reCAPTCHA v3 |
| 6 | Redes Sociales | WhatsApp, LinkedIn, GitHub, Telegram, Twitter, Instagram, YouTube |
| 7 | Seguridad & Sistema | Cambiar contraseña, Modo Seguro, Modo Mantenimiento, logs de acceso |

---

## API Endpoints

Todos los endpoints públicos devuelven `Content-Type: application/json`.

### Públicos (sin autenticación)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/secciones` | Secciones visibles del SPA |
| GET | `/api/tecnologias` | Stack de tecnologías visible |
| GET | `/api/portafolio` | Lista de proyectos |
| GET | `/api/portafolio/{id}` | Detalle de un proyecto |
| POST | `/api/contacto` | Enviar mensaje (requiere CSRF token) |
| GET | `/api/configuracion/tema` | Variables de color y tema |
| GET | `/api/configuracion/marca` | Nombre, logo URLs, favicon |
| GET | `/api/configuracion/sociales` | Solo claves `social_*` |
| GET | `/api/sistema/status` | Estado modo mantenimiento/seguro |
| GET | `/api/csrf` | Obtener token CSRF para el formulario SPA |

> Los endpoints de configuración exponen **únicamente** las claves de su whitelist. Nunca exponen tokens, passwords ni secrets.

### Protegidos (requieren sesión admin)

Todos bajo `/admin/*` — redirigen a `/admin/login` si no hay sesión activa.

---

## Subida de Imágenes

### Toggle Local / ImgBB

En los formularios de Portafolio y Tecnologías del admin aparece un toggle:

```
Fuente de imagen:  [● Local]  [○ ImgBB]
```

- **Local**: el archivo se sube a `/public/uploads/{carpeta}/` con nombre hasheado. Se guarda la ruta relativa en BD.
- **ImgBB**: el archivo se envía a `api.imgbb.com/1/upload` usando la API key configurada. Se guarda la URL `https://i.ibb.co/...` en BD.

El frontend recibe siempre un string de URL — no distingue el origen.

### Validaciones aplicadas

- MIME real verificado con `finfo` (no solo extensión)
- Tipos permitidos: `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `image/svg+xml`
- Tamaño máximo: 5 MB
- Nombre del archivo: `sha256(original + timestamp) + extensión`
- El directorio `uploads/` bloquea ejecución PHP vía `.htaccess`

---

## Notificaciones Telegram

### Configuración

1. Ir a Admin → Configuración → Pestaña 5 (APIs Externas)
2. Ingresar **Bot Token** y **Chat ID**
3. Clic en **"Probar conexión"** — verifica y envía mensaje de prueba

### Eventos notificados (configurables)

| Toggle | Evento |
|---|---|
| `telegram_notify_contacto` | Nuevo mensaje en el formulario de contacto |
| `telegram_notify_login_fail` | 3+ intentos de login fallidos desde la misma IP |
| `telegram_notify_nuevo_user` | Nuevo usuario admin registrado |
| `telegram_notify_config` | Cambio guardado en configuración |

---

## Email SMTP

### Configuración

Admin → Configuración → Pestaña 4 (Servidor de Email)

```
Host:          smtp.gmail.com
Puerto:        587
Cifrado:       TLS
Usuario:       tu@gmail.com
Contraseña:    (se guarda cifrada con AES-256-CBC)
```

La contraseña se cifra con `APP_KEY` antes de guardarse. Nunca se almacena en texto plano.

### Prueba

Clic en **"Enviar email de prueba"** — envía un correo al email admin configurado (`smtp_admin_copy`).

### Fallback

Si SMTP no está configurado, se usa `mail()` nativo de PHP como fallback.

---

## Seguridad

| Medida | Implementación |
|---|---|
| SQL Injection | PDO con `EMULATE_PREPARES=false` y sentencias preparadas en todos los queries |
| XSS | `htmlspecialchars()` en todas las salidas de vistas + `sanitizeSvg()` para SVG |
| CSRF | Token rotativo en cada POST, verificado por `CsrfMiddleware` |
| Contraseñas | `password_hash(BCRYPT)` para admin, AES-256-CBC para SMTP |
| Sesión | `httponly`, `samesite=Strict`, `secure` en producción, regeneración de ID cada 30 min |
| Rate limiting | Máx. 5 intentos de login por IP en 15 min (tabla `login_attempts`) |
| Formulario contacto | Máx. 3 mensajes por IP en 10 min |
| Uploads | Validación MIME real, nombre hasheado, bloqueo PHP en `/uploads/` |
| Headers HTTP | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin` |
| DocumentRoot | Solo `public/` es accesible — `app/`, `core/`, `config/` nunca desde web |
| SVG custom | `Security::sanitizeSvg()` — elimina `<script>`, atributos `on*`, URLs `javascript:` |

---

## Personalización del Tema

Los colores se cambian desde Admin → Configuración → Pestaña 3 (Colores & Tema).

Al guardar, `app.js` lee `/api/configuracion/tema` en cada carga e inyecta las variables en `:root`:

```js
document.documentElement.style.setProperty('--cyan',   '#00d4ff');
document.documentElement.style.setProperty('--purple', '#7b2d8b');
document.documentElement.style.setProperty('--orange', '#ff6b35');
// etc.
```

**No hay que editar CSS para cambiar la paleta.**

### Variables disponibles

| Variable CSS | Clave BD | Default |
|---|---|---|
| `--cyan` | `theme_color_cyan` | `#00d4ff` |
| `--purple` | `theme_color_purple` | `#7b2d8b` |
| `--orange` | `theme_color_orange` | `#ff6b35` |
| `--bg` | `theme_color_bg` | `#0b0f19` |
| `--text` | `theme_color_text` | `#e2e8f0` |

---

## Modo Mantenimiento y Modo Seguro

### Modo Mantenimiento

- Se activa desde Admin → Dashboard o Admin → Configuración → Pestaña 7
- Cuando está ON: cualquier visitante sin sesión admin ve la página `errors/503.php`
- El admin autenticado navega con normalidad
- El `Router.php` verifica `configuracion.modo_mantenimiento` antes de despachar cada ruta

### Modo Seguro

- Se activa desde Admin → Dashboard o Admin → Configuración → Pestaña 7
- Cuando está ON: los proyectos y secciones con `modo_seguro = 1` quedan ocultos públicamente
- Los datos no se eliminan — solo se filtran en las consultas API
- Útil para ocultar proyectos confidenciales o en desarrollo antes de presentaciones

---

## CDNs utilizados

El proyecto usa los siguientes recursos externos (requieren conexión a internet):

```html
<!-- Particles.js — red de partículas en el Hero -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

<!-- AOS.js — animaciones scroll reveal -->
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<!-- Devicons — iconos de tecnologías -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/devicon.min.css">

<!-- Quill.js — editor de texto enriquecido (solo admin) -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
```

---

## Flujo de Verificación

Checklist funcional tras la instalación:

- [ ] `http://codevnexus.test/` carga el SPA con pantalla de loading y luego secciones
- [ ] Typewriter y partículas visibles en el Hero
- [ ] `/admin/login` redirige a `/admin` tras login correcto
- [ ] `/admin` sin sesión → redirige a `/admin/login`
- [ ] Crear sección → aparece en `/api/secciones`
- [ ] Subir imagen **Local** en portafolio → archivo en `public/uploads/portafolio/` ✓
- [ ] Subir imagen **ImgBB** en portafolio → URL `i.ibb.co` guardada en BD ✓
- [ ] Agregar tecnología **Devicons** → icono renderiza en Stack ✓
- [ ] Agregar tecnología **SVG custom** → SVG sanitizado renderiza ✓
- [ ] Cambiar `theme_color_cyan` en Pestaña 3 → color activo cambia en toda la SPA ✓
- [ ] Cambiar `social_linkedin` en Pestaña 6 → link aparece en footer ✓
- [ ] Subir logo en Pestaña 2 → navbar muestra nuevo logo ✓
- [ ] Configurar SMTP y clic "Probar" → llega email de prueba ✓
- [ ] Configurar Telegram y clic "Probar" → llega mensaje en Telegram ✓
- [ ] Activar **Modo Mantenimiento** → público ve 503, admin navega normal ✓
- [ ] Activar **Modo Seguro** → proyectos marcados desaparecen del portafolio público ✓
- [ ] Enviar formulario contacto → mensaje en BD + email al admin + Telegram ✓
- [ ] 5+ intentos login fallidos → IP bloqueada temporalmente ✓

---

## Despliegue en producción (cPanel)

1. Subir todos los archivos excepto `config/config.php`
2. Crear `config/config.php` directamente en el servidor con los datos de producción
3. Cambiar `APP_ENV=production` → activa cookies `Secure` en sesiones
4. Asegurarse de que el `DocumentRoot` del dominio apunte a `/public`
5. Verificar que `mod_rewrite` esté habilitado en cPanel → `.htaccess` del Apache

---

## Licencia

Proyecto privado — uso personal. No redistribuir sin autorización del autor.

---

*CoDevNexus · Donde el código conecta mundos.*
