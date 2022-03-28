cls
SET PGPASSWORD=Orellana
pg_dump -U postgres -v -F c acomtus > c:\xampp\sistema_acomtus.dump
cls
xcopy c:\xampp\sistema_acomtus.dump "G:\Mi unidad\Respaldo" /y