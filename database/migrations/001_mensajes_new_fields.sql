-- ============================================================
-- Migration 001 — Nuevos campos en tabla mensajes
-- Ejecutar UNA SOLA VEZ en instalaciones existentes
-- ============================================================

ALTER TABLE `mensajes`
  ADD COLUMN IF NOT EXISTS `telefono`   VARCHAR(30)  NULL AFTER `correo`,
  ADD COLUMN IF NOT EXISTS `pais`       VARCHAR(80)  NULL AFTER `telefono`,
  ADD COLUMN IF NOT EXISTS `respondido` TINYINT(1)   NOT NULL DEFAULT 0 AFTER `leido`;
