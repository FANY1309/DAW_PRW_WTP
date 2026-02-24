-- WTP - Esquema MySQL (según Diagrama E/R)
-- Proyecto: WhosThatPokémon (WTP)

-- Inserción de datos de prueba para la tabla `usuario`
INSERT INTO `usuario` VALUES 
(1,'tester','tester@wtp.local','Usuario Tester','$2y$12$kjB8NwJRVXtdNqq2UjnMH.XdMy/sN6C8HH6ZbobRqq1z0J.jISv5y','2026-02-22 22:44:36',1,'usuario','j0ve1dk4m834lauivhn1es2uev','2026-02-23 20:21:52',NULL,NULL,NULL,'2026-02-23 20:21:52'),
(2,'admin','admin@wtp.local','Usuario Admin','$2y$12$kjB8NwJRVXtdNqq2UjnMH.XdMy/sN6C8HH6ZbobRqq1z0J.jISv5y','2026-02-22 22:44:36',1,'admin','snomemtdch3b43j0dcddm2v91i','2026-02-23 20:22:34',NULL,NULL,NULL,'2026-02-23 20:22:34');

-- Inserción de datos de prueba para la tabla `tipo`
INSERT INTO `tipo` VALUES 
(1,'electrico',1,'2026-02-18 17:27:16',NULL,NULL),
(2,'fuego',1,'2026-02-18 17:27:16',NULL,NULL);

-- Inserción de datos de prueba para la tabla `pokemon`
INSERT INTO `pokemon` VALUES (4,'Charmander',1,'naranja',0.60,8.50,'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/4.png',1,'2026-02-18 17:27:16',NULL,NULL),
(25,'Pikachu',1,'amarillo',0.40,6.00,'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png',1,'2026-02-18 17:27:16',NULL,NULL);

-- Inserción de datos de prueba para la tabla `pokemon_tipo`
INSERT INTO `pokemon_tipo` VALUES (2,4,2),(1,25,1);

-- Insersión de datos de prueba para la tabla `pokemon_tipo`
INSERT INTO `reto_diario` VALUES (1,'2026-02-18',1,25,1,'2026-02-18 17:27:16',NULL,NULL);