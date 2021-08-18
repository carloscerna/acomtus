select * from produccion_asignado where tiquete_desde BETWEEN 333301 and 333400 and codigo_inventario_tiquete = 23

select * from produccion_asignado where tiquete_desde BETWEEN 261701 and 261800 and codigo_inventario_tiquete = 23


SELECT pro_a.tiquete_desde, pro_a.tiquete_hasta, pro_a.codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha, 
                            cat_j.id_ as id_jornada, 
                            cat_r.descripcion as nombre_ruta, 
                            pro.codigo_transporte_colectivo, cat_est.descripcion as descripcion_estatus 
                            FROM produccion_asignado pro_a
                            INNER JOIN produccion pro On pro.id_ = pro_a.codigo_produccion 
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada 
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta 
                            INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro.codigo_estatus 
                            WHERE tiquete_desde BETWEEN 261701 and 261800 and pro_a.codigo_inventario_tiquete = 23
							

SELECT pro_a.tiquete_desde, pro_a.tiquete_hasta, pro_a.codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha,
                        cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta,
                        btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) AS nombre_completo, per.codigo,
                        pro.numero_vueltas, pro.total_ingreso, pro.codigo_transporte_colectivo,
                        cat_est.descripcion as descripcion_estatus,
                        btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) AS nombre_unidad
                        FROM produccion_asignado pro_a
                        INNER JOIN produccion pro On pro.id_ = pro_a.codigo_produccion
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                        INNER JOIN personal per ON per.codigo = pro.codigo_personal
                        INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro.codigo_estatus
                        INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
						    WHERE tiquete_desde BETWEEN 261701 and 261800 and pro_a.codigo_inventario_tiquete = 23