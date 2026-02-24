-- WTP - Esquema de base de datos (según Diagrama E/R)
-- Pendiente de volcar el modelo exacto del diagrama.

-- WTP - Esquema MySQL (según Diagrama E/R)
-- Proyecto: WhosThatPokémon (WTP)
-- Nota: Relación Pokémon—Tipo es N:M, se implementa con tabla puente.
-- El reto diario se asocia al “Pokémon-tipo” (tabla puente). (ver justificación del E/R)

SET NAMES utf8mb4;
-- DESCOMENTAR ESTAS LINEAS EN LOCAL, PRE O DESARROLLO
-- DROP DATABASE IF EXISTS wtp;
-- CREATE DATABASE wtp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE wtp;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) USUARIO
DROP TABLE IF EXISTS partida;
DROP TABLE IF EXISTS reto_diario;
DROP TABLE IF EXISTS pokemon_tipo;
DROP TABLE IF EXISTS pokemon;
DROP TABLE IF EXISTS tipo;
DROP TABLE IF EXISTS usuario;

CREATE TABLE usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL,
  email VARCHAR(120) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  password VARCHAR(255) NOT NULL,

  fechaRegistro DATETIME NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  rol VARCHAR(20) NOT NULL,

  idSesion VARCHAR(128) NULL,
  ultimoSesion DATETIME NULL,

  creadoPor INT NULL,
  fechaCreacion DATETIME NULL,
  modificadoPorUsuario INT NULL,
  fechaUltimaModificacion DATETIME NULL,

  UNIQUE KEY uq_usuario_usuario (usuario),
  UNIQUE KEY uq_usuario_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) TIPO
CREATE TABLE tipo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,

  creadoPor INT NULL,
  fechaCreacion DATETIME NULL,
  modificadoPorUsuario INT NULL,
  fechaUltimaModificacion DATETIME NULL,

  UNIQUE KEY uq_tipo_nombre (nombre),

  CONSTRAINT fk_tipo_creadoPor
    FOREIGN KEY (creadoPor) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tipo_modificadoPor
    FOREIGN KEY (modificadoPorUsuario) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) POKEMON
CREATE TABLE pokemon (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  generacion INT NULL,
  color VARCHAR(50) NULL,

  altura DECIMAL(6,2) NULL,
  peso DECIMAL(6,2) NULL,
  imagen VARCHAR(255) NULL,

  creadoPor INT NULL,
  fechaCreacion DATETIME NULL,
  modificadoPorUsuario INT NULL,
  fechaUltimaModificacion DATETIME NULL,

  UNIQUE KEY uq_pokemon_nombre (nombre),

  CONSTRAINT fk_pokemon_creadoPor
    FOREIGN KEY (creadoPor) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_pokemon_modificadoPor
    FOREIGN KEY (modificadoPorUsuario) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) TABLA PUENTE: POKEMON_TIPO (N:M)
-- Relación “es” entre Pokémon y Tipo (N:M) -> tabla puente.
CREATE TABLE pokemon_tipo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idPokemon INT NOT NULL,
  idTipo INT NOT NULL,

  UNIQUE KEY uq_pokemon_tipo (idPokemon, idTipo),

  CONSTRAINT fk_pt_pokemon
    FOREIGN KEY (idPokemon) REFERENCES pokemon(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pt_tipo
    FOREIGN KEY (idTipo) REFERENCES tipo(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) RETO_DIARIO
CREATE TABLE reto_diario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 0,

  idPokemon INT NOT NULL,

  creadoPorUsuario INT NULL,
  fechaCreacion DATETIME NULL,
  modificadoPorUsuario INT NULL,
  fechaUltimaModificacion DATETIME NULL,

  CONSTRAINT fk_reto_pokemon
    FOREIGN KEY (idPokemon) REFERENCES pokemon(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_reto_creadoPor
    FOREIGN KEY (creadoPorUsuario) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_reto_modificadoPor
    FOREIGN KEY (modificadoPorUsuario) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  UNIQUE KEY uq_reto_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 6) PARTIDA
CREATE TABLE partida (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATETIME NOT NULL,

  idUsuario INT NOT NULL,
  idReto INT NOT NULL,

  resultado VARCHAR(50) NULL,

  creadoPorUsuario INT NULL,
  fechaCreacion DATETIME NULL,
  modificadoPorUsuario INT NULL,
  fechaUltimaModificacion DATETIME NULL,

  CONSTRAINT fk_partida_usuario
    FOREIGN KEY (idUsuario) REFERENCES usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_partida_reto
    FOREIGN KEY (idReto) REFERENCES reto_diario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_partida_creadoPor
    FOREIGN KEY (creadoPorUsuario) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_partida_modificadoPor
    FOREIGN KEY (modificadoPorUsuario) REFERENCES usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
