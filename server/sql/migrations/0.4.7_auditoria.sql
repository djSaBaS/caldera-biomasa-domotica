-- Comentario: Ampliar estados sin reinterpretar históricos ni eliminar CHECK.
ALTER TABLE telemetry MODIFY boiler_state ENUM('OFF','CHECK','CHC','ACC','STB','REC','NORMAL','MOD','MAN','SIC','SPE','ALT') NOT NULL;

-- Comentario: Añadir perfiles por fase sin asignar recetas de fábrica a filas existentes.
ALTER TABLE boiler_config
    ADD COLUMN acc_on_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN acc_off_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN stb_on_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN stb_off_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN normal_on_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN normal_off_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN mod_on_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN mod_off_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN man_on_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN man_off_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN man_pause_minutes SMALLINT UNSIGNED NULL,
    ADD COLUMN man_work_seconds SMALLINT UNSIGNED NULL;
