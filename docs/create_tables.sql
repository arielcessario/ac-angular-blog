# POSTS
CREATE TABLE posts (
  post_id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) NOT NULL DEFAULT 0,
  titulo varchar(45) DEFAULT NULL,
  detalle varchar(2000) DEFAULT NULL,
  fecha timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status int(11) DEFAULT NULL COMMENT '0 - Baja, 1 - Activo, 2 - Terminado, 3 - Dinero Transferido',
  en_slider int(11) DEFAULT 0 COMMENT '0 - No, 1 - Si',
  vistas int(11) DEFAULT 0 COMMENT 'Contador de vistas',
  up_votes int(11) DEFAULT 0 COMMENT 'Votos Positivos',
  down_votes int(11) DEFAULT 0 COMMENT 'Votos Negativos',
  PRIMARY KEY (post_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# FOTOS DE POSTS -
CREATE TABLE posts_fotos (
  post_foto_id int(11) NOT NULL AUTO_INCREMENT,
  post_id int(11) DEFAULT NULL,
  main int(11) DEFAULT 0 COMMENT '0 - No Main 1- Foto principal',
  nombre varchar(45) DEFAULT NULL,
  carpeta varchar(45) DEFAULT NULL,
  PRIMARY KEY (post_foto_id),
  KEY FOTOS_POST_IDX (post_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# TEMAS -
CREATE TABLE temas (
  tema_id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(100) NOT NULL,
  parent_id int(11) DEFAULT '-1',
  status int(11) DEFAULT NULL COMMENT '0 - Baja, 1 - Activo, 2 - Terminado, 3 - Dinero Transferido',
  PRIMARY KEY (tema_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# TEMAS DE POSTS -
CREATE TABLE posts_temas (
  post_tema_id int(11) NOT NULL AUTO_INCREMENT,
  post_id int(11) DEFAULT NULL,
  tema_id int(11) DEFAULT 0,
  PRIMARY KEY (post_tema_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
