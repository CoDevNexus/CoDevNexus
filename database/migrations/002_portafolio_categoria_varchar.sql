-- Migration: change portafolio.categoria from ENUM to VARCHAR(80)
-- This allows custom category values beyond the original 6 hardcoded ones.
ALTER TABLE portafolio MODIFY COLUMN categoria VARCHAR(80) NOT NULL DEFAULT 'otro';
