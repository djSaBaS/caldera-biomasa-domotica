# Migraciones SQL

`schema.sql` crea instalaciones nuevas; `CREATE TABLE IF NOT EXISTS` **no actualiza** tablas ya creadas. La revisión 0.4.7 incorpora `migrations/0.4.7_auditoria.sql`.

Para una base existente 0.4.6: realizar y verificar una copia, detener escritores de la aplicación, revisar el SQL y ejecutarlo **una vez** sobre la base seleccionada. Amplía ENUM con CHC/REC conservando CHECK histórico y añade doce columnas NULL de tiempos por fase. No carga recetas ni cambia versiones existentes. La operación DDL no tiene rollback transaccional y no es idempotente; registrar manualmente su aplicación. No se ha migrado ninguna base productiva.

```bash
mysql -u usuario -p nombre_base < server/sql/migrations/0.4.7_auditoria.sql
```

Comprobar columnas y estados, y después publicar backend y ambos firmwares de forma coordinada. Incrementar `config_version` cuando se alteren parámetros; un contador igual con contenido distinto se rechaza. NULL en perfiles existentes conserva el legado simétrico y deshabilita MAN hasta configuración explícita. Si se revierte una aplicación, conservar las columnas nuevas y el ENUM ampliado; no borrar históricos CHC/REC ni reducir tipos automáticamente.

Las pruebas `mysql_integration.php` requieren base desechable cuyo nombre termine en `_ci` y la recrean desde cero. `migration_integration.php` prueba el SQL real en tablas temporales derivadas del esquema con las diferencias relevantes de 0.4.6, verificando preservación de CHECK, versión 70000 y perfiles nuevos NULL. No sustituye una migración ensayada con copia de la instalación real.
