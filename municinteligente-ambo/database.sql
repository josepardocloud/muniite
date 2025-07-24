-- Creación de la base de datos
CREATE DATABASE municinteligente_ambo;
USE municinteligente_ambo;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(15),
    direccion TEXT,
    distrito_id INT,
    fecha_nacimiento DATE,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    rol ENUM('admin', 'funcionario', 'ciudadano') DEFAULT 'ciudadano',
    huella_dactilar TEXT,
    ultimo_acceso DATETIME,
    FOREIGN KEY (distrito_id) REFERENCES distritos(id)
);

-- Tabla de distritos
CREATE TABLE distritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(10) UNIQUE NOT NULL,
    es_rural BOOLEAN DEFAULT FALSE,
    poblacion_urbana INT,
    poblacion_rural INT
);

-- Tabla de trámites
CREATE TABLE tramites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    costo DECIMAL(10,2) DEFAULT 0.00,
    duracion_estimada INT COMMENT 'En días hábiles',
    requiere_pago BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    fase_implementacion ENUM('1', '2', '3') DEFAULT '1'
);

-- Tabla de solicitudes de trámites
CREATE TABLE solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_tramite VARCHAR(50) UNIQUE NOT NULL,
    usuario_id INT NOT NULL,
    tramite_id INT NOT NULL,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'en_proceso', 'aprobado', 'rechazado', 'completado') DEFAULT 'pendiente',
    fecha_finalizacion DATETIME,
    datos_json TEXT COMMENT 'Datos específicos del trámite en formato JSON',
    hash_blockchain VARCHAR(255),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (tramite_id) REFERENCES tramites(id)
);

-- Tabla de pagos
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME,
    metodo_pago ENUM('tarjeta', 'transferencia', 'efectivo', 'billetera_digital'),
    codigo_transaccion VARCHAR(100),
    estado ENUM('pendiente', 'completado', 'rechazado', 'reembolsado') DEFAULT 'pendiente',
    comprobante_path TEXT,
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
);

-- Tabla de documentos
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    tipo_documento VARCHAR(50) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo TEXT NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    hash_verificacion VARCHAR(255),
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
);

-- Tabla de participación ciudadana
CREATE TABLE participacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('presupuesto', 'votacion', 'consulta', 'reporte'),
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME,
    estado ENUM('activo', 'cerrado', 'en_analisis', 'implementado'),
    distrito_id INT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (distrito_id) REFERENCES distritos(id)
);

-- Tabla de votaciones
CREATE TABLE votaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_voto DATETIME DEFAULT CURRENT_TIMESTAMP,
    opcion_voto VARCHAR(50) NOT NULL,
    hash_blockchain VARCHAR(255),
    FOREIGN KEY (participacion_id) REFERENCES participacion(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de reportes ciudadanos
CREATE TABLE reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    direccion TEXT,
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8),
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('recibido', 'en_revision', 'en_proceso', 'resuelto', 'rechazado') DEFAULT 'recibido',
    fecha_resolucion DATETIME,
    comentario_resolucion TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de transparencia
CREATE TABLE transparencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('contrato', 'presupuesto', 'obra', 'adquisicion', 'informe'),
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_evento DATE,
    monto DECIMAL(15,2),
    documento_path TEXT,
    hash_blockchain VARCHAR(255),
    visible BOOLEAN DEFAULT TRUE
);

-- Tabla de noticias
CREATE TABLE noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    imagen_path TEXT,
    fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL,
    destacada BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de logs
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    registro_id INT,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    fecha_log DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);