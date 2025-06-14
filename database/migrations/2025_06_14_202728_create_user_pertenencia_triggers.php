<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Esta migración crea los triggers necesarios para mantener la integridad de las relaciones entre usuarios (pertenece), gerencias y unidades administrativas.
// De todos modos se recomienda dejar los observadores de Eloquent para manejar la lógica de negocio en el nivel de aplicación.
// El error que puede ocurrir es que al acualizar de gerencia o unidad administrativa...si un usuario no tiene el rol esperado, no se acualiza el usuario pero si la otra tabla.
return new class extends Migration {
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER update_gerencia_pertenencia
            BEFORE UPDATE ON users
            FOR EACH ROW
            BEGIN
                IF NEW.pertenece_id IS NOT NULL AND NEW.rol_cache IN ("gerente", "subgerente") THEN
                    UPDATE gerencias
                    SET gerente_id = IF(NEW.rol_cache = "gerente", NEW.id, gerente_id),
                        subgerente_id = IF(NEW.rol_cache = "subgerente", NEW.id, subgerente_id)
                    WHERE id = NEW.pertenece_id;
                END IF;
            END;
        ');

        DB::unprepared('
            CREATE TRIGGER update_unidad_pertenencia
            BEFORE UPDATE ON users
            FOR EACH ROW
            BEGIN
                IF NEW.pertenece_id IS NOT NULL AND NEW.rol_cache = "administrador-unidad" THEN
                    UPDATE unidades_administrativas
                    SET administrador_id = NEW.id
                    WHERE id = NEW.pertenece_id;
                END IF;
            END;
        ');
        DB::unprepared('
            CREATE TRIGGER update_user_from_gerencia
            BEFORE UPDATE ON gerencias
            FOR EACH ROW
            BEGIN
                IF NEW.gerente_id IS NOT NULL THEN
                    UPDATE users SET pertenece_id = NEW.id WHERE id = NEW.gerente_id AND rol_cache = "gerente";
                END IF;
                IF NEW.subgerente_id IS NOT NULL THEN
                    UPDATE users SET pertenece_id = NEW.id WHERE id = NEW.subgerente_id AND rol_cache = "subgerente";
                END IF;
            END;   
        ');
        DB::unprepared('
            CREATE TRIGGER update_user_from_unidad
            BEFORE UPDATE ON unidades_administrativas
            FOR EACH ROW
            BEGIN
                IF NEW.administrador_id IS NOT NULL THEN
                    UPDATE users SET pertenece_id = NEW.id WHERE id = NEW.administrador_id AND rol_cache = "administrador-unidad";
                END IF;
            END;   
        ');

    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS update_gerencia_pertenencia');
        DB::unprepared('DROP TRIGGER IF EXISTS update_unidad_pertenencia');
        DB::unprepared('DROP TRIGGER IF EXISTS update_user_from_gerencia');
        DB::unprepared('DROP TRIGGER IF EXISTS update_user_from_unidad');
    }
};
