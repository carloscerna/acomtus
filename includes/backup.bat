cls
SET PGPASSWORD=Orellana
pg_dump -U postgres -v -F c acomtus > c:\wamp64\www\sistema_acomtus.dump
cls
xcopy c:\wamp64\www\sistema_acomtus.dump "G:\Mi unidad\Respaldo" /y