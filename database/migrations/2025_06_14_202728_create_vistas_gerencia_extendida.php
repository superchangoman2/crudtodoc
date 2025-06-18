<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // down() no funciona con vistas, entonces eliminamos la vista en up()
        DB::unprepared('DROP VIEW IF EXISTS vista_gerencias_extendida_admin');
        DB::unprepared('DROP VIEW IF EXISTS vista_gerencias_extendida');

        // Y las creamos nuevamente
        DB::unprepared('
            CREATE VIEW vista_gerencias_extendida_admin AS
                SELECT
                    g.id,
                    g.nombre,
                    g.unidad_administrativa_id,
                    ua.nombre AS unidad_administrativa_nombre,

                    (
                        SELECT u.id FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "gerente"
                        ORDER BY u.id LIMIT 1
                    ) AS gerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "gerente"
                        ORDER BY u.id LIMIT 1
                    ) AS gerente_email,

                    (
                        SELECT u.id FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "subgerente"
                        ORDER BY u.id LIMIT 1
                    ) AS subgerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "subgerente"
                        ORDER BY u.id LIMIT 1
                    ) AS subgerente_email,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "admin"
                    ) AS usuarios_admin_ids,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "usuario"
                    ) AS usuarios_user_ids

                FROM gerencias g
                JOIN unidades_administrativas ua ON ua.id = g.unidad_administrativa_id;
        ');

        DB::unprepared('
            CREATE VIEW vista_gerencias_extendida AS
                SELECT
                    g.id,
                    g.nombre,
                    g.unidad_administrativa_id,
                    ua.nombre AS unidad_administrativa_nombre,

                    (
                        SELECT u.id FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "gerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS gerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "gerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS gerente_email,

                    (
                        SELECT u.id FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "subgerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS subgerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "subgerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS subgerente_email,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "admin" AND u.deleted_at IS NULL
                    ) AS usuarios_admin_ids,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "usuario" AND u.deleted_at IS NULL
                    ) AS usuarios_user_ids

                FROM gerencias g
                JOIN unidades_administrativas ua ON ua.id = g.unidad_administrativa_id AND ua.deleted_at IS NULL
                WHERE g.deleted_at IS NULL;
        ');

    }

    public function down(): void
    {
    }
};
