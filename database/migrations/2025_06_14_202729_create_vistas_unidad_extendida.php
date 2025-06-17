<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // down() no funciona con vistas, entonces eliminamos la vista en up()
        DB::unprepared('DROP VIEW IF EXISTS vista_unidades_extendida_admin');
        DB::unprepared('DROP VIEW IF EXISTS vista_unidades_extendida');

        // Y las creamos nuevamente
        DB::unprepared('
            CREATE VIEW vista_unidades_extendida_admin AS
                SELECT
                    ua.id,
                    ua.nombre,

                    (
                        SELECT u.id FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = ua.id AND r.name = "administrador-unidad"
                        ORDER BY u.id LIMIT 1
                    ) AS administrador_id,

                    (
                        SELECT u.email FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = ua.id AND r.name = "administrador-unidad"
                        ORDER BY u.id LIMIT 1
                    ) AS administrador_email,

                    (
                        SELECT GROUP_CONCAT(g.id ORDER BY g.nombre SEPARATOR ",")
                        FROM gerencias g
                        WHERE g.unidad_administrativa_id = ua.id
                    ) AS gerencias_ids,

                    (
                        SELECT GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ", ")
                        FROM gerencias g
                        WHERE g.unidad_administrativa_id = ua.id
                    ) AS gerencias_nombres,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "admin"
                    ) AS usuarios_admin_ids,

                    (
                        SELECT u.id FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "gerente"
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_gerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "gerente"
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_gerente_email,

                    (
                        SELECT u.id FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "subgerente"
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_subgerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "subgerente"
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_subgerente_email,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "user"
                    ) AS usuarios_user_ids

                FROM unidades_administrativas ua;
        ');

        DB::unprepared('
            CREATE VIEW vista_unidades_extendida AS
                SELECT
                    ua.id,
                    ua.nombre,

                    (
                        SELECT u.id FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = ua.id AND r.name = "administrador-unidad" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS administrador_id,

                    (
                        SELECT u.email FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = ua.id AND r.name = "administrador-unidad" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS administrador_email,

                    (
                        SELECT GROUP_CONCAT(g.id ORDER BY g.nombre SEPARATOR ",")
                        FROM gerencias g
                        WHERE g.unidad_administrativa_id = ua.id AND g.deleted_at IS NULL
                    ) AS gerencias_ids,

                    (
                        SELECT GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ", ")
                        FROM gerencias g
                        WHERE g.unidad_administrativa_id = ua.id AND g.deleted_at IS NULL
                    ) AS gerencias_nombres,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id AND g.deleted_at IS NULL
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "admin" AND u.deleted_at IS NULL
                    ) AS usuarios_admin_ids,

                    (
                        SELECT u.id FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id AND g.deleted_at IS NULL
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "gerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_gerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id AND g.deleted_at IS NULL
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "gerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_gerente_email,

                    (
                        SELECT u.id FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id AND g.deleted_at IS NULL
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "subgerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_subgerente_id,

                    (
                        SELECT u.email FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id AND g.deleted_at IS NULL
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "subgerente" AND u.deleted_at IS NULL
                        ORDER BY u.id LIMIT 1
                    ) AS usuario_subgerente_email,

                    (
                        SELECT GROUP_CONCAT(u.id ORDER BY u.id)
                        FROM users u
                        JOIN gerencias g ON g.id = u.pertenece_id AND g.deleted_at IS NULL
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE g.unidad_administrativa_id = ua.id AND r.name = "user" AND u.deleted_at IS NULL
                    ) AS usuarios_user_ids

                FROM unidades_administrativas ua
                WHERE ua.deleted_at IS NULL;
        ');
    }
    public function down(): void
    {
    }
};
