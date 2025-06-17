<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // down() no funciona con vistas, entonces eliminamos la vista en up()
        DB::unprepared('DROP VIEW IF EXISTS vista_gerencias_extendida_admin');
        DB::unprepared('DROP VIEW IF EXISTS vista_unidades_extendida_admin');

        // Y las creamos nuevamente
        DB::unprepared('
            CREATE VIEW vista_gerencias_extendida_admin AS
                SELECT
                    g.id,
                    g.nombre,
                    g.unidad_administrativa_id,

                    (
                        SELECT u.id
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "gerente"
                        ORDER BY u.id
                        LIMIT 1
                    ) AS gerente_id,

                    (
                        SELECT u.id
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "subgerente"
                        ORDER BY u.id
                        LIMIT 1
                    ) AS subgerente_id,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name IN ("admin", "gerente", "subgerente", "user")
                    ) AS usuarios_ids

                FROM gerencias g;
        ');

        // Vista de unidades que incluye usuarios eliminados (soft deleted)
        DB::unprepared('
            CREATE VIEW vista_unidades_extendida_admin AS
                SELECT
                    ua.id,
                    ua.nombre,

                    (
                        SELECT u.id
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = ua.id AND r.name = "administrador-unidad"
                        ORDER BY u.id
                        LIMIT 1
                    ) AS administrador_id,

                    (
                        SELECT GROUP_CONCAT(g.id ORDER BY g.nombre SEPARATOR ",")
                        FROM gerencias g
                        WHERE g.unidad_administrativa_id = ua.id
                    ) AS gerencias_ids,

                    (
                        SELECT GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ", ")
                        FROM gerencias g
                        WHERE g.unidad_administrativa_id = ua.id
                    ) AS gerencias_nombres

                FROM unidades_administrativas ua;
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vista_gerencias_extendida_admin');
        DB::unprepared('DROP VIEW IF EXISTS vista_unidades_extendida_admin');
    }
};
