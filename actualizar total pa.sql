SELECT pa.cantidad, pa.total , pa.codigo_produccion, pa.codigo_inventario_tiquete,
it.precio_publico, it.id_
FROM produccion_asignado pa
INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
where codigo_produccion = 374

UPDATE produccion_asignado SET
total = cantidad * (SELECT it.precio_publico FROM inventario_tiquete it WHERE it.id_ = codigo_inventario_tiquete)
where codigo_produccion = 374


select * from produccion where id_ = 374
select * from produccion_asignado where codigo_produccion = 374

SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, cat_ts.descripcion as nombre_serie, pa.id_ as id_produccion_asignado, 
pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.procesado, pa.cantidad, pa.total, pa.codigo_estatus, pa.tiquete_cola, 
btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
cat_r.descripcion as descripcion_ruta, it.precio_publico, cat_e.descripcion as descripcion_estatus 
FROM produccion p 
INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_ 
INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete 
INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
INNER JOIN catalogo_estatus cat_e ON cat_e.codigo = pa.codigo_estatus 
WHERE pa.codigo_produccion = '374' 
ORDER BY pa.id_, pa.codigo_inventario_tiquete
