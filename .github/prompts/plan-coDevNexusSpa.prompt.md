# Plan: SPA CoDevNexus MVC — Full Dynamic

**Resumen:** PHP 8 MVC puro, SPA dark industrial, panel admin con upload dual (local + ImgBB por imagen), redes sociales dinámicas desde BD, selector de iconos con Devicons + SVG personalizado, CSRF/PDO/XSS, notificaciones Telegram y Modo Seguro por proyecto. Panel `/admin/configuracion` centralizado con pestañas para: datos de empresa, logos, colores del tema, servidor de email SMTP, APIs externas (Telegram, ImgBB, reCAPTCHA) y redes sociales. Cero código hardcodeado en frontend ni en config files.

---

## Identidad Visual

- **Estilo:** Modern Tech / Dark Industrial
- **Fondo:** `#0b0f19` con resplandor sutil azul cian/eléctrico
- **Colores:**
  - `--cyan: #00d4ff` — elementos activos, glow, bordes
  - `--purple: #7b2d8b` — secciones de desarrollo, acento biográfico
  - `--orange: #ff6b35` — alertas, hover de portafolio, "Nexus"
- **Typewriter Hero:** `"Cargando módulos de: Diseño Web... Infraestructura de Redes... IoT... Automatización..."`
- **Animaciones:** AOS.js (scroll reveal) + Particles.js (red de partículas en Hero)

---

## Modificaciones obligatorias integradas

### ① Imágenes dinámicas con toggle Local / ImgBB por imagen

En el formulario de Portafolio y Tecnologías del admin:

```
Fuente de imagen: [● Local]  [○ ImgBB]

  — Si Local:  input file → upload a /public/uploads/portafolio/ → guarda ruta relativa
  — Si ImgBB:  input URL o input file → POST a api.imgbb.com/1/upload → guarda URL externa
```

- `core/ImageUploader.php` — clase que detecta el toggle y ejecuta la estrategia correcta
- La BD guarda solo el string final (`/uploads/...` o `https://i.ibb.co/...`) — el frontend no distingue
- API key de ImgBB guardada en tabla `configuracion`

### ② Redes Sociales dinámicas en Configuración

Nuevas claves en tabla `configuracion`:

| clave | ejemplo |
|---|---|
| `social_whatsapp` | `+593999999999` |
| `social_linkedin` | `https://linkedin.com/in/tuusuario` |
| `social_github` | `https://github.com/tuusuario` |
| `social_telegram` | `@tuusuario` (opcional) |

- El footer y sección contacto los leen desde `/api/configuracion/sociales`
- Endpoint público — solo expone claves `social_*`, nunca tokens ni secrets

### ③ Selector de Iconos Stack — Devicons + SVG custom

En el formulario de Tecnologías del admin:

```
Modo icono:  [● Devicons]  [○ SVG personalizado]

  — Devicons:   búsqueda en lista de 200+ logos → guarda clase CSS (ej: devicon-php-plain)
  — SVG custom: textarea para pegar código SVG → guarda el SVG sanitizado en BD
```

- `Security::sanitizeSvg()` — strip de scripts/eventos antes de guardar
- Devicons cargado como CDN CSS, lista de búsqueda es JSON local (sin petición externa)

### ④ ImgBB toggle por imagen

- Estrategia: patrón Strategy — el controlador no sabe si es local o ImgBB
- `ImageUploader::upload(file, toggle)` devuelve el string final de URL siempre

### ⑤ Panel de Configuración Centralizado — TODO desde el admin

Una sola vista `/admin/configuracion` organizada en **pestañas**:

#### Pestaña 1 — Empresa & Marca
```
[ Nombre del sitio       ]   [ Tagline / Slogan         ]
[ Correo de contacto     ]   [ Teléfono                 ]
[ Dirección              ]   [ País / Ciudad            ]
[ Texto del Footer       ]
```

#### Pestaña 2 — Logos & Favicon
```
Logo Principal:   [imagen actual]  [Subir nuevo]  (Local o ImgBB)
Favicon:          [favicon actual] [Subir nuevo]
Logo Admin Panel: [imagen actual]  [Subir nuevo]
```
- Los logos se sirven al frontend via `/api/configuracion/marca`
- `<img src>` en layouts lee BD, no un path hardcodeado

#### Pestaña 3 — Colores & Tema
```
Color Cian (activos):    [#00d4ff] [preview ████]
Color Morado (dev):      [#7b2d8b] [preview ████]
Color Naranja (nexus):   [#ff6b35] [preview ████]
Color Fondo:             [#0b0f19] [preview ████]
Color Texto:             [#e2e8f0] [preview ████]
Particulas visibles:     [● Sí]  [○ No]
Intensidad glow:         [slider 0-100]
```
- El frontend lee `/api/configuracion/tema` e inyecta variables CSS en `:root` dinámicamente
- Cambio de color en admin → se refleja en el sitio sin tocar CSS

#### Pestaña 4 — Servidor de Email (SMTP)
```
[ Host SMTP              ]   [ Puerto     ]  [● TLS  ○ SSL  ○ None]
[ Usuario SMTP           ]   [ Contraseña ]
[ Email remitente        ]   [ Nombre remitente       ]
[ Email copia admin      ]   (recibe copia de contactos)
[Enviar email de prueba →]
```
- `core/Mailer.php` — wrapper PHPMailer-like usando `mail()` nativo o SMTP con fsockopen
- Si SMTP no configurado → `mail()` como fallback
- Botón **"Enviar email de prueba"** → `POST /admin/configuracion/test-email` → envía al correo admin

#### Pestaña 5 — APIs Externas
```
Telegram:
  [ Bot Token             ]   [ Chat ID    ]  [Probar conexión →]

ImgBB:
  [ API Key               ]   [Verificar key →]

reCAPTCHA v3:
  [ Site Key              ]   [ Secret Key ]

Tipos de notificaciones Telegram:
  [✓] Nuevo mensaje de contacto
  [✓] Login fallido (3+ intentos)
  [✓] Nuevo usuario registrado
  [ ] Cambio de configuración
```

#### Pestaña 6 — Redes Sociales
```
[ WhatsApp (número)      ]   [ LinkedIn URL           ]
[ GitHub URL             ]   [ Telegram (@usuario)    ]
[ Twitter/X URL          ]   [ Instagram URL          ]
[ YouTube URL            ]   [ Sitio externo URL      ]
```
- Solo se muestran en el footer los que tengan valor (no links vacíos)

#### Pestaña 7 — Seguridad & Sistema
```
Cambiar contraseña admin:
  [ Contraseña actual ]  [ Nueva contraseña ]  [ Confirmar ]

Modo Seguro:  [● OFF]  [○ ON]  — oculta proyectos/secciones marcados
Modo Mantenimiento: [● OFF]  [○ ON]  — muestra página 503 al público
Logs de accesos fallidos: [Ver últimos 20]
```

---

## Estructura de Archivos Completa

```
codevnexus/
├── public/                             ← DocumentRoot del servidor web
│   ├── index.php                       ← Front Controller (único punto de entrada)
│   ├── .htaccess                       ← Rewrite todo → index.php + headers seguridad
│   ├── uploads/
│   │   ├── portafolio/                 ← imágenes subidas localmente
│       ├── tecnologias/
│       └── branding/                   ← logos y favicon subidos
│   └── assets/
│       ├── css/
│       │   ├── style.css               ← Dark Industrial theme
│       │   └── admin.css
│       ├── js/
│       │   ├── app.js                  ← SPA: typewriter, fetch, AOS, render dinámico
│       │   └── particles-config.js
│       └── img/
│           └── logo.svg
│
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php          ← sirve el shell SPA
│   │   ├── Api/
│   │   │   ├── SeccionesController.php
│   │   │   ├── TecnologiasController.php
│   │   │   ├── PortafolioController.php
│   │   │   ├── ContactoController.php      ← guarda mensaje + dispara Telegram
│   │   │   ├── ConfiguracionController.php ← endpoints /sociales, /tema, /marca públicos
│   │   │   └── SistemaController.php        ← endpoint /api/sistema/status (modo mantenimiento)
│   │   └── Admin/
│   │       ├── AuthController.php          ← login / logout con password_verify()
│   │       ├── DashboardController.php     ← métricas, toggle Modo Seguro global
│   │       ├── SeccionesController.php     ← CRUD + Quill.js
│   │       ├── PortafolioController.php    ← CRUD + upload dual Local/ImgBB
│   │       ├── TecnologiasController.php   ← CRUD + selector Devicons/SVG
│   │       ├── MensajesController.php      ← bandeja de entrada
│   │       └── ConfiguracionController.php ← 7 pestañas: empresa, logos, tema, SMTP, APIs, sociales, seguridad
│   │
│   ├── Models/
│   │   ├── SeccionModel.php
│   │   ├── TecnologiaModel.php          ← campo icono_tipo ENUM(devicons, svg_custom)
│   │   ├── PortafolioModel.php
│   │   ├── MensajeModel.php
│   │   ├── ConfiguracionModel.php       ← get/set por clave, batch update
│   │   └── AdminUserModel.php
│   │
│   ├── Views/
│   │   ├── layouts/
│   │   │   ├── main.php                 ← layout público (CDNs, meta CSP)
│   │   │   └── admin.php                ← sidebar + nav admin
│   │   ├── home/
│   │   │   └── index.php                ← shell SPA (div#app + scripts)
│   │   └── admin/
│   │       ├── login.php
│   │       ├── dashboard.php
│   │       ├── secciones/               ← index.php, create.php, edit.php
│   │       ├── portafolio/              ← index.php, create.php, edit.php (upload dual)
│   │       ├── tecnologias/             ← index.php, create.php, edit.php (Devicons/SVG)
│   │       ├── mensajes/                ← index.php, ver.php
│   │       └── configuracion/           ← index.php (sociales, tokens, contraseña)
│   │
│   └── Middleware/
│       ├── AuthMiddleware.php           ← protege todas las rutas /admin
│       └── CsrfMiddleware.php           ← verifica token en todo POST
│
├── core/
│   ├── Router.php                       ← get(), post(), group(), middleware por ruta
│   ├── Controller.php                   ← base: render(), json(), redirect()
│   ├── Model.php                        ← base: PDO, find(), findAll(), create(), update(), delete()
│   ├── Request.php                      ← encapsula $_GET, $_POST, $_FILES, $_SERVER
│   ├── Response.php                     ← headers, JSON, redirect
│   ├── Database.php                     ← Singleton PDO, utf8mb4, emulate_prepares=false
│   ├── Security.php                     ← generateCsrfToken(), verifyCsrfToken(), sanitize(), sanitizeSvg(), rateLimit()
│   ├── ImageUploader.php                ← estrategia Local vs ImgBB por toggle
│   ├── Mailer.php                       ← SMTP configurable desde BD, fallback mail(), método testConnection()
│   └── Telegram.php                     ← sendMessage(), testConnection(), notificaciones tipificadas
│
├── config/
│   ├── config.php                       ← SOLO DB_*, APP_KEY, APP_ENV (todo lo demás en tabla configuracion)
│   ├── routes.php                       ← definición de todas las rutas
│   └── session.php                      ← sesión segura (httponly, samesite, firmada)
│
├── database/
│   └── schema.sql
│
└── .gitignore                           ← excluir config.php, /uploads, logs
```

---

## Base de Datos — Tablas

### `admin_users`
| campo | tipo | notas |
|---|---|---|
| id | INT UNSIGNED PK | |
| username | VARCHAR(60) UNIQUE | |
| password | VARCHAR(255) | password_hash() BCRYPT |
| created_at | TIMESTAMP | |

### `secciones`
| campo | tipo | notas |
|---|---|---|
| id | INT UNSIGNED PK | |
| titulo | VARCHAR(200) | |
| contenido | LONGTEXT | HTML enriquecido Quill |
| tipo_seccion | ENUM | hero, sobre, portafolio, servicios, contacto, blog, otro |
| orden | TINYINT UNSIGNED | |
| visible | TINYINT(1) | |
| modo_seguro | TINYINT(1) | ocultar en Modo Seguro |
| updated_at | TIMESTAMP | ON UPDATE |

### `tecnologias`
| campo | tipo | notas |
|---|---|---|
| id | INT UNSIGNED PK | |
| nombre | VARCHAR(80) | |
| nivel | TINYINT UNSIGNED | 0-100 (porcentaje) |
| icono_tipo | ENUM | devicons, svg_custom |
| icono_valor | TEXT | clase CSS o SVG sanitizado |
| categoria | ENUM | lenguaje, framework, base_datos, red, devops, iot, otro |
| visible | TINYINT(1) | |
| orden | TINYINT UNSIGNED | |

### `portafolio`
| campo | tipo | notas |
|---|---|---|
| id | INT UNSIGNED PK | |
| titulo | VARCHAR(200) | |
| descripcion_corta | VARCHAR(300) | |
| descripcion_larga | LONGTEXT | HTML enriquecido Quill |
| categoria | ENUM | redes, software, iot, automatizacion, web, otro |
| imagen_url | VARCHAR(400) | ruta local o URL ImgBB |
| enlace_demo | VARCHAR(400) | |
| enlace_repo | VARCHAR(400) | |
| modo_seguro | TINYINT(1) | ocultar en Modo Seguro |
| visible | TINYINT(1) | |
| orden | TINYINT UNSIGNED | |
| created_at | TIMESTAMP | |

### `mensajes`
| campo | tipo | notas |
|---|---|---|
| id | INT UNSIGNED PK | |
| nombre | VARCHAR(120) | |
| correo | VARCHAR(200) | |
| asunto | VARCHAR(250) | |
| mensaje | TEXT | |
| ip_origen | VARCHAR(45) | IPv4 o IPv6 |
| user_agent | VARCHAR(500) | |
| leido | TINYINT(1) | |
| fecha | TIMESTAMP | |

### `configuracion`
Clave → Valor. Agrupadas por módulo:

```
── Empresa & Marca ──────────────────────────────────────
site_name              CoDevNexus
site_tagline           Donde el código conecta mundos
site_email             contacto@codevnexus.tech
site_phone             +593999999999
site_address           Ecuador
site_footer_text       © 2026 CoDevNexus. Todos los derechos reservados.

── Logos ────────────────────────────────────────────────
logo_principal         /uploads/branding/logo.svg
logo_admin             /uploads/branding/logo-admin.svg
favicon                /uploads/branding/favicon.ico

── Tema / Colores ───────────────────────────────────────
theme_color_cyan       #00d4ff
theme_color_purple     #7b2d8b
theme_color_orange     #ff6b35
theme_color_bg         #0b0f19
theme_color_text       #e2e8f0
theme_particles        1
theme_glow_intensity   70

── SMTP ─────────────────────────────────────────────────
smtp_host              smtp.gmail.com
smtp_port              587
smtp_encryption        tls
smtp_user              (vacío)
smtp_password          (vacío, cifrado AES)
smtp_from_email        no-reply@codevnexus.tech
smtp_from_name         CoDevNexus
smtp_admin_copy        admin@codevnexus.tech

── Telegram ─────────────────────────────────────────────
telegram_bot_token     (vacío)
telegram_chat_id       (vacío)
telegram_notify_contacto  1
telegram_notify_login_fail 1
telegram_notify_nuevo_user 1
telegram_notify_config    0

── APIs externas ────────────────────────────────────────
imgbb_api_key          (vacío)
recaptcha_site_key     (vacío)
recaptcha_secret       (vacío)

── Redes Sociales ───────────────────────────────────────
social_whatsapp        (vacío)
social_linkedin        (vacío)
social_github          (vacío)
social_telegram        (vacío)
social_twitter         (vacío)
social_instagram       (vacío)
social_youtube         (vacío)
social_website         (vacío)

── Sistema & Seguridad ──────────────────────────────────
modo_seguro            0
modo_mantenimiento     0
mantenimiento_mensaje  Sitio en mantenimiento. Volvemos pronto.
```

---

## Rutas (config/routes.php)

```
GET  /                              → HomeController@index
GET  /api/secciones                 → Api\SeccionesController@index
GET  /api/tecnologias               → Api\TecnologiasController@index
GET  /api/portafolio                → Api\PortafolioController@index
GET  /api/portafolio/{id}           → Api\PortafolioController@show
POST /api/contacto                  → Api\ContactoController@store       [CsrfMiddleware]
GET  /api/configuracion/sociales    → Api\ConfiguracionController@sociales
GET  /api/configuracion/tema        → Api\ConfiguracionController@tema
GET  /api/configuracion/marca       → Api\ConfiguracionController@marca
GET  /api/sistema/status            → Api\SistemaController@status

GET  /admin                         → Admin\DashboardController@index    [AuthMiddleware]
GET  /admin/login                   → Admin\AuthController@showLogin
POST /admin/login                   → Admin\AuthController@login
GET  /admin/logout                  → Admin\AuthController@logout        [AuthMiddleware]
GET  /admin/secciones               → Admin\SeccionesController@index    [AuthMiddleware]
POST /admin/secciones/store         → Admin\SeccionesController@store    [AuthMiddleware, CsrfMiddleware]
POST /admin/secciones/update/{id}   → Admin\SeccionesController@update   [AuthMiddleware, CsrfMiddleware]
POST /admin/secciones/delete/{id}   → Admin\SeccionesController@delete   [AuthMiddleware, CsrfMiddleware]
... (mismo patrón para portafolio, tecnologias, mensajes)
GET  /admin/configuracion           → Admin\ConfiguracionController@index  [AuthMiddleware]
POST /admin/configuracion/update           → Admin\ConfiguracionController@update         [AuthMiddleware, CsrfMiddleware]
POST /admin/configuracion/test-email       → Admin\ConfiguracionController@testEmail       [AuthMiddleware, CsrfMiddleware]
POST /admin/configuracion/test-telegram    → Admin\ConfiguracionController@testTelegram    [AuthMiddleware, CsrfMiddleware]
POST /admin/configuracion/test-imgbb       → Admin\ConfiguracionController@testImgbb       [AuthMiddleware, CsrfMiddleware]
POST /admin/configuracion/upload-logo      → Admin\ConfiguracionController@uploadLogo      [AuthMiddleware, CsrfMiddleware]
POST /admin/modo-seguro/toggle             → Admin\DashboardController@toggleModoSeguro    [AuthMiddleware, CsrfMiddleware]
POST /admin/modo-mantenimiento/toggle      → Admin\DashboardController@toggleMantenimiento [AuthMiddleware, CsrfMiddleware]
```

---

## Steps de Implementación (orden)

1. `database/schema.sql` — Schema completo con todas las claves de `configuracion` como datos semilla
2. `config/config.php` + `config/session.php` — Solo DB_*, APP_KEY, APP_ENV (mínimo indispensable)
3. `core/Database.php` + `core/Router.php` + `core/Controller.php` + `core/Model.php` + `core/Request.php` + `core/Response.php`
4. `core/Security.php` — CSRF, sanitize, sanitizeSvg, rateLimit, encryptSmtpPassword/decrypt
5. `core/ImageUploader.php` — estrategia Local / ImgBB
6. `core/Telegram.php` — sendMessage(), testConnection(), notificaciones tipificadas
7. `core/Mailer.php` — SMTP desde BD (host/port/enc/user/pass), fallback mail(), testConnection()
8. `config/routes.php` — todas las rutas incluyendo nuevos endpoints de config y sistema
9. `public/index.php` — Front Controller bootstrap
10. `public/.htaccess` — Rewrite + headers seguridad + bloqueo /uploads/*.php
11. `app/Middleware/AuthMiddleware.php` + `CsrfMiddleware.php`
12. `app/Models/ConfiguracionModel.php` — get(), set(), getBatch(grupo), setBatch(), getPublic(whitelist)
13. `app/Models/` — resto de modelos
14. `app/Controllers/Api/` — endpoints JSON incluyendo /tema, /marca, /sociales, /sistema/status
15. `app/Controllers/Admin/ConfiguracionController.php` — 7 pestañas + test endpoints
16. `app/Controllers/Admin/` — resto CRUD
17. `app/Views/layouts/main.php` — lee logo/favicon/colores desde API al cargar
18. `app/Views/layouts/admin.php` — sidebar con logo admin desde BD
19. `app/Views/home/index.php` — shell SPA
20. `app/Views/admin/configuracion/index.php` — UI 7 pestañas con JS inline para preview de colores
21. `app/Views/admin/` — resto de vistas
22. `public/assets/css/style.css` — variables CSS como defaults, override dinámico desde JS
23. `public/assets/css/admin.css` — panel dark sidebar
24. `public/assets/js/app.js` — boot: fetch /api/configuracion/tema → inyecta :root vars, luego carga SPA
25. `public/assets/js/particles-config.js` — config leída desde tema

---

## CDNs del Frontend

```html
<!-- Particles.js -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<!-- AOS.js -->
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<!-- Devicons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/devicon.min.css">
<!-- Quill.js (admin) -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
```

---

## Seguridad — Checklist

- [x] PDO con `ERRMODE_EXCEPTION` y `EMULATE_PREPARES = false`
- [x] CSRF Tokens en todos los formularios POST
- [x] `htmlspecialchars()` en todas las salidas
- [x] `sanitizeSvg()` via DOMDocument antes de persistir SVG
- [x] `password_hash()` BCRYPT en registro, `password_verify()` en login
- [x] Sesión con `httponly`, `samesite=Strict`, `secure` (prod)
- [x] Rate limit por IP en contacto y login
- [x] `public/` como DocumentRoot — `app/`, `core/`, `config/` inaccesibles desde web
- [x] `.htaccess` bloquea acceso directo a carpetas sensibles
- [x] Headers HTTP: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`
- [x] Uploads: validación MIME real (no solo extensión), renombrado con hash, carpeta sin ejecución PHP

---

## Verification

- Importar `schema.sql` en Laragon → `http://codevnexus.test/`
- SPA carga secciones dinámicas desde `/api/secciones`
- Typewriter y partículas visibles en Hero
- Subir imagen local → `/uploads/portafolio/` ✓
- Subir imagen ImgBB → URL `i.ibb.co` en BD ✓
- Agregar tecnología Devicons → icono renderiza en Stack ✓
- Agregar tecnología SVG custom → SVG sanitizado renderiza ✓
- Cambiar `social_linkedin` en Config pestaña 6 → footer actualiza sin tocar código ✓
- Cambiar `theme_color_cyan` en Config pestaña 3 → color activo cambia en toda la SPA ✓
- Subir logo en Config pestaña 2 → navbar y footer muestran nuevo logo ✓
- Configurar SMTP en Config pestaña 4 → botón "Probar" recibe email de prueba ✓
- Configurar Telegram en Config pestaña 5 → botón "Probar" recibe mensaje en Telegram ✓
- Activar Modo Mantenimiento → público ve página 503, admin accede con normalidad ✓
- Enviar formulario contacto → mensaje en BD + email al admin + Telegram ✓
- Activar Modo Seguro → proyectos marcados desaparecen del portafolio ✓
- `/admin` sin sesión → redirige a `/admin/login` ✓

---

## Decisiones de Diseño

- **PHP puro sin frameworks** — control total, cero dependencias externas, deploy simple en Laragon/cPanel
- **Front Controller único** `public/index.php` — todas las rutas pasan por el Router
- **API interna REST JSON** — el JS es el cliente, el PHP es puramente datos
- **`ImageUploader` patrón Strategy** — el controlador no sabe si es Local o ImgBB
- **Endpoints de config con whitelist** — `/api/configuracion/sociales|tema|marca` solo exponen claves permitidas explícitamente, nunca tokens ni passwords
- **SMTP password cifrado** — se guarda en BD con AES-256 usando APP_KEY, nunca en texto plano
- **`Mailer.php` autónomo** — lee credenciales SMTP de BD en cada envío (sin cache), así el admin puede cambiarlas en caliente
- **Tema dinámico** — `app.js` hace fetch a `/api/configuracion/tema` al boot y aplica `document.documentElement.style.setProperty('--cyan', valor)` para cada variable CSS
- **Logo dinámico** — `main.php` y `admin.php` leen `logo_principal`/`logo_admin` de BD, no path hardcodeado
- **Modo Mantenimiento** — `Router.php` chequea `configuracion.modo_mantenimiento` antes de despachar; si activo y no es admin autenticado → render 503
- **Devicons como CDN** + lista JSON local para búsqueda sin petición externa
- **`sanitizeSvg()` via DOMDocument** — strip de `<script>`, atributos `on*`, `javascript:` antes de persistir
