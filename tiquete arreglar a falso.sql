select * from produccion
WHERE codigo_inventario_tiquete = 18 and id_ = 525

select * from produccion_asignado
WHERE codigo_produccion = 530

select * from inventario_tiquete;

select * from produccion_correlativo
WHERE codigo_produccion_asignacion = 3851

update produccion_asignado pa SET
codigo_inventario_tiquete = (SELECT p.codigo_inventario_tiquete FROM produccion p WHERE p.id_ = pa.codigo_produccion) 

--
update produccion_asignado set
codigo_inventario_tiquete = 21
where id_ >= 3806 and id_ <= 3809

update produccion_asignado set
codigo_inventario_tiquete = 21
where id_ >= 3813 and id_ <= 3816

update produccion_asignado set
codigo_inventario_tiquete = 21
where id_ >= 3821 and id_ <= 3823

update produccion_asignado set
codigo_inventario_tiquete = 21
where id_ >= 3832 and id_ <= 3835

update produccion_correlativo
set procesado = 'false'
where correlativo >= 959384 and correlativo <= 959400

select * from produccion_correlativo where correlativo >= 959384 and correlativo <= 959400
