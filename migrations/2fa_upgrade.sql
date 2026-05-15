-- ============================================
--  AUTOZONE — Migración: 2FA Profesional
--  Fecha: 2026-05-15
--  Descripción: Agrega columnas para OTP seguro
-- ============================================

-- Agregar columnas para 2FA profesional
ALTER TABLE Usuario
  ADD COLUMN otp_hash     VARCHAR(255) NULL    COMMENT 'Hash bcrypt del código OTP',
  ADD COLUMN otp_expira   DATETIME     NULL    COMMENT 'Fecha/hora de expiración del OTP (5 min)',
  ADD COLUMN otp_intentos TINYINT      NOT NULL DEFAULT 0 COMMENT 'Intentos fallidos de verificación (máx 5)';

-- Limpiar cualquier OTP residual existente
UPDATE Usuario SET codigo_2fa = NULL, estado_2fa = 0;
