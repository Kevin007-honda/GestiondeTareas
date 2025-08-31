-- Script para actualizar la tabla notificaciones existente
-- Agregar columna tarea_id para permitir redirección a tareas específicas

USE gestion_tareas_escolares;

-- Verificar si la columna ya existe antes de agregarla
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = 'gestion_tareas_escolares' 
     AND table_name = 'notificaciones' 
     AND column_name = 'tarea_id') = 0,
    'ALTER TABLE notificaciones ADD COLUMN tarea_id INT NULL AFTER mensaje',
    'SELECT "Column tarea_id already exists" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign key constraint si no existe
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE CONSTRAINT_SCHEMA = 'gestion_tareas_escolares' 
     AND TABLE_NAME = 'notificaciones' 
     AND CONSTRAINT_NAME = 'fk_notificaciones_tarea') = 0,
    'ALTER TABLE notificaciones ADD CONSTRAINT fk_notificaciones_tarea FOREIGN KEY (tarea_id) REFERENCES tareas(id)',
    'SELECT "Foreign key constraint already exists" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Tabla notificaciones actualizada correctamente' as resultado;
