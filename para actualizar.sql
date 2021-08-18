select * from produccion where fecha = '2020-11-18' order by id_, fecha;

select * from produccionOLd where fecha = '2020-11-15' order by id_, fecha;

select id_, codigo_ruta, codigo_inventario_tiquete from produccion where fecha = '2020-11-16' order by id_, fecha;

-- primer paso arreglar la tabla producción partiendo de la tabla Producción Old.
--update produccion pro SET codigo_ruta = (SELECT codigo_ruta FROM produccionold WHERE id_ = pro.id_)
--update produccion pro SET codigo_inventario_tiquete = (SELECT codigo_inventario_tiquete FROM produccion_asignado WHERE codigo_produccion = pro.id_ LIMIT 1)

--update produccion pro SET codigo_inventario_tiquete = (SELECT codigo_inventario_tiquete FROM produccionold WHERE id_ = pro.id_)
select * from inventario_tiquete
--select * from catalogo_ruta
--select * from produccion_asignado where codigo_produccion = 847
--select * from produccion_correlativo where codigo_produccion_asignacion = 6206

/*

update produccion set codigo_ruta = 1 where id_ >=950 and id_ <= 963;
update produccion set codigo_ruta = 1 where id_ >=964 and id_ <= 971;
update produccion set codigo_ruta = 2 where id_ >=972 and id_ <= 983;
update produccion set codigo_ruta = 2 where id_ >=984 and id_ <= 991;
update produccion set codigo_ruta = 3 where id_ >=992 and id_ <= 997;
update produccion set codigo_ruta = 6 where id_ >=998 and id_ <= 998;
update produccion set codigo_ruta = 5 where id_ >=999 and id_ <= 1000;
update produccion set codigo_ruta = 4 where id_ >=1001 and id_ <= 1004;
update produccion set codigo_ruta = 4 where id_ >=1005 and id_ <= 1010;
update produccion set codigo_ruta = 8 where id_ >=1011 and id_ <= 1020;


update produccion set codigo_inventario_tiquete = 17 where id_ >=950 and id_ <= 963;
update produccion set codigo_inventario_tiquete = 21 where id_ >=964 and id_ <= 971;
update produccion set codigo_inventario_tiquete = 17 where id_ >=972 and id_ <= 983;
update produccion set codigo_inventario_tiquete = 21 where id_ >=984 and id_ <= 991;
update produccion set codigo_inventario_tiquete = 21 where id_ >=992 and id_ <= 997;
update produccion set codigo_inventario_tiquete = 21 where id_ >=998 and id_ <= 998;
update produccion set codigo_inventario_tiquete = 21 where id_ >=999 and id_ <= 1000;
update produccion set codigo_inventario_tiquete = 17 where id_ >=1001 and id_ <= 1004;
update produccion set codigo_inventario_tiquete = 21 where id_ >=1005 and id_ <= 1010;
update produccion set codigo_inventario_tiquete = 16 where id_ >=1011 and id_ <= 1020;
select codigo_produccion, codigo_inventario_tiquete from produccion_asignado where codigo_produccion = 1011