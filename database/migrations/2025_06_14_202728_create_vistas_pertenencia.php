<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // down() no funciona con vistas, entonces eliminamos la vista en up()
        DB::unprepared('DROP VIEW IF EXISTS vista_gerencias_con_responsables');
        DB::unprepared('DROP VIEW IF EXISTS vista_unidades_con_administrador');

        // y luego las creamos
        DB::unprepared('
            CREATE VIEW vista_gerencias_con_responsables AS
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
                        LIMIT 1
                    ) AS gerente_id,

                    (
                        SELECT u.id
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = g.id AND r.name = "subgerente"
                        LIMIT 1
                    ) AS subgerente_id

                FROM gerencias g;
        ');

        DB::unprepared('
            CREATE VIEW vista_unidades_con_administrador AS
                SELECT
                    ua.id,
                    ua.nombre,
                    
                    (
                        SELECT u.id
                        FROM users u
                        JOIN model_has_roles mr ON mr.model_id = u.id
                        JOIN roles r ON r.id = mr.role_id
                        WHERE u.pertenece_id = ua.id AND r.name = "administrador-unidad"
                        LIMIT 1
                    ) AS administrador_id

                FROM unidades_administrativas ua;
        ');
    }

    public function down(): void
    {
    }
};
