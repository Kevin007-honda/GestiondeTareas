USE gestion_tareas_escolares;

-- Insertar roles solo si no existen
INSERT IGNORE INTO roles (nombre) VALUES 
('administrador'),
('profesor'),
('estudiante');

-- Ya no insertamos estados_tarea aquí porque se insertaron en schema.sql

-- Insertar usuarios con contraseñas sin hashear
-- Administrador (solo uno)
INSERT INTO usuarios (username, password, email, nombre, apellidos, rol_id, activo) VALUES
('admin', 'admin123', 'admin@escuela.edu', 'Administrador', 'Principal', 1, 1);

-- Profesor (solo uno)
INSERT INTO usuarios (username, password, email, nombre, apellidos, rol_id, activo) VALUES
('profesor1', 'prof123', 'profesor1@escuela.edu', 'Juan', 'Pérez', 2, 1);

-- Estudiante (solo uno)
INSERT INTO usuarios (username, password, email, nombre, apellidos, rol_id, activo) VALUES
('estudiante1', 'est123', 'estudiante1@escuela.edu', 'Ana', 'Martínez', 3, 1);

-- Grupos
INSERT INTO grupos (nombre, descripcion, activo) VALUES
('1°A Secundaria', 'Primer grado grupo A de secundaria', 1),
('1°B Secundaria', 'Primer grado grupo B de secundaria', 1);

-- Materias
INSERT INTO materias (nombre, codigo, descripcion, activo) VALUES
('Matemáticas I', 'MAT101', 'Curso básico de matemáticas', 1),
('Español', 'ESP101', 'Lengua y literatura española', 1),
('Ciencias Naturales', 'CN101', 'Introducción a las ciencias naturales', 1);

-- Asignar profesor a grupos
INSERT INTO profesor_grupo (profesor_id, grupo_id) VALUES
((SELECT id FROM usuarios WHERE username = 'profesor1'), 1),
((SELECT id FROM usuarios WHERE username = 'profesor1'), 2);

-- Asignar estudiante a grupo
INSERT INTO estudiante_grupo (estudiante_id, grupo_id) VALUES
((SELECT id FROM usuarios WHERE username = 'estudiante1'), 1);

-- Asignar profesor a materias
INSERT INTO profesor_materia (profesor_id, materia_id) VALUES
((SELECT id FROM usuarios WHERE username = 'profesor1'), 1), -- Matemáticas
((SELECT id FROM usuarios WHERE username = 'profesor1'), 2), -- Español
((SELECT id FROM usuarios WHERE username = 'profesor1'), 3); -- Ciencias Naturales

-- Asignar materias a grupos con su profesor
INSERT INTO grupo_materia (grupo_id, materia_id, profesor_id) VALUES
(1, 1, (SELECT id FROM usuarios WHERE username = 'profesor1')),
(1, 2, (SELECT id FROM usuarios WHERE username = 'profesor1')),
(2, 1, (SELECT id FROM usuarios WHERE username = 'profesor1')),
(2, 3, (SELECT id FROM usuarios WHERE username = 'profesor1'));

-- Crear algunas tareas de ejemplo
INSERT INTO tareas (titulo, descripcion, fecha_entrega, materia_id, grupo_id, profesor_id, estado_id) VALUES
('Ejercicios de Álgebra', 'Resolver problemas del capítulo 1', DATE_ADD(NOW(), INTERVAL 7 DAY), 1, 1, 
 (SELECT id FROM usuarios WHERE username = 'profesor1'), 1),
('Ensayo de Literatura', 'Escribir ensayo sobre Don Quijote', DATE_ADD(NOW(), INTERVAL 14 DAY), 2, 1,
 (SELECT id FROM usuarios WHERE username = 'profesor1'), 1);

-- Crear algunas entregas de tareas
INSERT INTO entregas_tarea (tarea_id, estudiante_id, estado_id, comentarios) VALUES
(1, (SELECT id FROM usuarios WHERE username = 'estudiante1'), 2, 'Primera entrega de ejercicios'),
(2, (SELECT id FROM usuarios WHERE username = 'estudiante1'), 3, 'Entregado a tiempo');

-- Crear algunas notificaciones
INSERT INTO notificaciones (usuario_id, titulo, mensaje, leida) VALUES
((SELECT id FROM usuarios WHERE username = 'estudiante1'), 
 'Recordatorio de tarea', 'No olvides entregar los ejercicios de álgebra', 0);
