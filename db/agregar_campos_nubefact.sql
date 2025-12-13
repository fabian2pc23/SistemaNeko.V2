-- =====================================================
-- ============================================
-- AGREGAR CAMPOS PARA FACTURACIÓN ELECTRÓNICA
-- Sistema Dual: Greenter (SUNAT directo) + NubeFact
-- ============================================

-- Ejecutar este script si los campos no existen

-- Campo para PDF de NubeFact
ALTER TABLE venta ADD COLUMN IF NOT EXISTS pdf_nubefact VARCHAR(500) DEFAULT NULL;

-- Campos para XML y CDR de NubeFact (servicios en la nube)
ALTER TABLE venta ADD COLUMN IF NOT EXISTS xml_nubefact VARCHAR(500) DEFAULT NULL;
ALTER TABLE venta ADD COLUMN IF NOT EXISTS cdr_nubefact VARCHAR(500) DEFAULT NULL;

-- Campos para XML y CDR de Greenter (archivos locales)
ALTER TABLE venta ADD COLUMN IF NOT EXISTS xml_local VARCHAR(255) DEFAULT NULL;
ALTER TABLE venta ADD COLUMN IF NOT EXISTS cdr_local VARCHAR(255) DEFAULT NULL;

-- ============================================
-- VERIFICAR ESTRUCTURA
-- ============================================
-- SELECT pdf_nubefact, xml_nubefact, cdr_nubefact, xml_local, cdr_local FROM venta LIMIT 1;
