-- ============================================================
-- CoDevNexus — Schema limpio para instalación nueva
-- Charset: utf8mb4 | Engine: InnoDB
-- No contiene credenciales ni datos personales.
-- El instalador web (public/install.php) completa la configuración.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- admin_users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(60)      NOT NULL,
  `password`   VARCHAR(255)     NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- El usuario administrador se crea mediante el instalador web (public/install.php)

-- ------------------------------------------------------------
-- secciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `secciones` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`       VARCHAR(200)     NOT NULL,
  `contenido`    LONGTEXT,
  `tipo_seccion` ENUM('hero','sobre','portafolio','tecnologias','servicios','contacto','blog','otro') NOT NULL DEFAULT 'otro',
  `orden`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `visible`      TINYINT(1)       NOT NULL DEFAULT 1,
  `modo_seguro`  TINYINT(1)       NOT NULL DEFAULT 0,
  `updated_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `secciones` (`titulo`, `contenido`, `tipo_seccion`, `orden`, `visible`) VALUES
('Hero',              '<p>Bienvenido a tu portfolio.</p>',           'hero',         1, 1),
('Sobre mí',         '<p>Cuéntanos un poco sobre ti.</p>',          'sobre',        2, 1),
('Portafolio',       '<p>Tus proyectos destacados.</p>',            'portafolio',   3, 1),
('Stack Tecnológico','<p>Tecnologías y herramientas que usas.</p>', 'tecnologias',  4, 1),
('Servicios',        '<p>Servicios que ofreces.</p>',               'servicios',    5, 1),
('Contacto',         '<p>Formulario de contacto.</p>',              'contacto',     6, 1);

-- ------------------------------------------------------------
-- tecnologias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tecnologias` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(80)      NOT NULL,
  `nivel`       TINYINT UNSIGNED NOT NULL DEFAULT 50,
  `icono_tipo`  VARCHAR(20) NOT NULL DEFAULT 'devicon',
  `icono_valor` TEXT,
  `categoria`   ENUM('lenguaje','framework','base_datos','red','devops','iot','otro') NOT NULL DEFAULT 'otro',
  `visible`     TINYINT(1)       NOT NULL DEFAULT 1,
  `orden`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed de tecnologías eliminado — agrégalas desde el panel admin.

-- ------------------------------------------------------------
-- portafolio
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portafolio` (
  `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`             VARCHAR(200)     NOT NULL,
  `descripcion_corta`  VARCHAR(300),
  `descripcion_larga`  LONGTEXT,
  `categoria`          VARCHAR(80) NOT NULL DEFAULT 'otro',
  `imagen_url`         VARCHAR(400),
  `enlace_demo`        VARCHAR(400),
  `enlace_repo`        VARCHAR(400),
  `modo_seguro`        TINYINT(1)       NOT NULL DEFAULT 0,
  `visible`            TINYINT(1)       NOT NULL DEFAULT 1,
  `orden`              TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- mensajes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mensajes` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(120)  NOT NULL,
  `correo`      VARCHAR(200)  NOT NULL,
  `telefono`    VARCHAR(30)   NULL,
  `pais`        VARCHAR(80)   NULL,
  `asunto`      VARCHAR(250),
  `mensaje`     TEXT          NOT NULL,
  `ip_origen`   VARCHAR(45),
  `user_agent`  VARCHAR(500),
  `leido`       TINYINT(1)    NOT NULL DEFAULT 0,
  `respondido`  TINYINT(1)    NOT NULL DEFAULT 0,
  `fecha`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- configuracion (clave → valor)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` VARCHAR(80)  NOT NULL,
  `valor` TEXT,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
-- Empresa & Marca (valores vacíos — el instalador los define)
('site_name',              ''),
('site_tagline',           ''),
('site_email',             ''),
('site_phone',             ''),
('site_address',           ''),
('site_footer_text',       ''),
-- Logos
('logo_principal',         ''),
('logo_admin',             ''),
('favicon',                ''),
-- Tema / Colores (defaults seguros)
('theme_color_cyan',       '#00d4ff'),
('theme_color_purple',     '#7b2d8b'),
('theme_color_orange',     '#ff6b35'),
('theme_color_bg',         '#0b0f19'),
('theme_color_text',       '#e2e8f0'),
('theme_particles',        '1'),
('theme_glow_intensity',   '70'),
('particles_style',        'network'),
-- Typewriter
('typewriter_lines',       ''),
('typewriter_color',       '#00d4ff'),
('typewriter_size',        '1.25'),
('typewriter_speed',       '80'),
('typewriter_pause',       '1800'),
-- Email
('mail_driver',            'smtp'),
-- Gmail
('gmail_user',             ''),
('gmail_app_password',     ''),
('gmail_from_name',        ''),
('gmail_admin_copy',       ''),
-- SMTP personalizado
('smtp_host',              ''),
('smtp_port',              '587'),
('smtp_encryption',        'tls'),
('smtp_user',              ''),
('smtp_password',          ''),
('smtp_from_email',        ''),
('smtp_from_name',         ''),
('smtp_admin_copy',        ''),
-- Telegram
('telegram_bot_token',          ''),
('telegram_chat_id',            ''),
('telegram_notify_contacto',    '1'),
('telegram_notify_login_fail',  '1'),
('telegram_notify_nuevo_user',  '1'),
('telegram_notify_config',      '0'),
-- APIs externas
('imgbb_api_key',          ''),
('recaptcha_site_key',     ''),
('recaptcha_secret',       ''),
-- Redes Sociales
('social_whatsapp',        ''),
('social_linkedin',        ''),
('social_github',          ''),
('social_telegram',        ''),
('social_twitter',         ''),
('social_instagram',       ''),
('social_youtube',         ''),
('social_website',         ''),
-- Sistema & Seguridad
('modo_seguro',            '0'),
('modo_mantenimiento',     '0'),
('mantenimiento_mensaje',  'Sitio en mantenimiento. Volvemos pronto.');

-- ------------------------------------------------------------
-- login_attempts (rate limiting)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `username`   VARCHAR(60),
  `intentado_en` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_library` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `url`         VARCHAR(500) NOT NULL,
  `driver`      ENUM('local','imgbb') NOT NULL DEFAULT 'local',
  `filename`    VARCHAR(255),
  `mime`        VARCHAR(80),
  `size`        INT UNSIGNED DEFAULT 0,
  `creado_en`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_driver` (`driver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

