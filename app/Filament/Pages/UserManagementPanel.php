<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;

class UserManagementPanel extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Usuarios y Accesos';
    protected static ?string $navigationLabel = 'Estructura organizativa';
    protected static string $view = 'filament.pages.user-management-panel';
    protected static ?string $title = 'Estructura organizativa';

    public function getTableQuery(): Builder
    {
        $authUser = auth()->user();
        $rol = $authUser->getRoleNames()->first();
        $perteneceId = $authUser->pertenece_id;

        if (!$rol || !$perteneceId) {
            return User::query()->whereRaw('0 = 1');
        }

        $rolesInferiores = User::rolesInferioresA($rol);

        // ADMIN:
        if ($rol === 'admin') {
            return User::query()
                ->select('users.*')
                ->with('roles')
                ->where('id', '<>', $authUser->id)
                ->whereHas('roles', fn($q) => $q->whereIn('name', $rolesInferiores));
        }

        // ADMINISTRADOR-UNIDAD
        if ($rol === 'administrador-unidad') {
            $vista = DB::table('vista_unidades_extendida')->where('id', $perteneceId)->first();

            $ids = collect([
                $vista->usuario_gerente_id,
                $vista->usuario_subgerente_id,
            ])
                ->merge(explode(',', $vista->usuarios_user_ids ?? ''))
                ->map(fn($id) => (int) trim($id));

            // Agregar usuarios sin pertenencia (gerente, subgerente y usuario)
            $sinPertenencia = User::query()
                ->select('id')
                ->whereNull('pertenece_id')
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['gerente', 'subgerente', 'usuario']))
                ->pluck('id')
                ->toArray();

            $ids = $ids
                ->merge($sinPertenencia)
                ->filter()
                ->unique()
                ->toArray();

            return User::query()
                ->select('users.*')
                ->with('roles')
                ->whereIn('id', $ids)
                ->whereHas('roles', fn($q) => $q->whereIn('name', $rolesInferiores));
        }

        // GERENTE o SUBGERENTE
        if (in_array($rol, ['gerente', 'subgerente'])) {
            $vista = DB::table('vista_gerencias_extendida')->where('id', $perteneceId)->first();

            $ids = collect();

            if ($rol === 'gerente') {
                $ids = collect([
                    $vista->subgerente_id,
                ])
                    ->merge(explode(',', $vista->usuarios_user_ids ?? ''));

                $sinGerencia = User::query()
                    ->select('id')
                    ->whereNull('pertenece_id')
                    ->whereHas('roles', fn($q) => $q->whereIn('name', ['subgerente', 'usuario']))
                    ->pluck('id')
                    ->toArray();

                $ids = $ids->merge($sinGerencia);
            }

            if ($rol === 'subgerente') {
                $ids = collect(explode(',', $vista->usuarios_user_ids ?? ''));

                $usuariosSinGerencia = User::query()
                    ->select('id')
                    ->whereNull('pertenece_id')
                    ->whereHas('roles', fn($q) => $q->where('name', 'usuario'))
                    ->pluck('id')
                    ->toArray();

                $ids = $ids->merge($usuariosSinGerencia);
            }

            $ids = $ids
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->toArray();

            return User::query()
                ->select('users.*')
                ->with('roles')
                ->whereIn('id', $ids)
                ->whereHas('roles', fn($q) => $q->whereIn('name', $rolesInferiores));
        }

        return User::query()->whereRaw('0 = 1');
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Nombre completo')
                ->getStateUsing(fn($record) => $record->name)
                ->sortable(query: function ($query, $direction) {
                    return $query
                        ->orderBy('first_name', $direction)
                        ->orderBy('last_name', $direction);
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
                }),

            TextColumn::make('email')
                ->label('Correo')
                ->sortable()
                ->searchable(),

            TextColumn::make('roles.name')
                ->label('Rol')
                ->sortable()
                ->searchable()
                ->formatStateUsing(fn($state) => ucfirst($state)),

            TextColumn::make('pertenencia')
                ->label('Pertenencia')
                ->getStateUsing(function (User $record) {
                    if (!$record->pertenece_id) {
                        return '-';
                    }

                    $rol = $record->getRoleNames()->first();

                    if ($rol === 'administrador-unidad') {
                        $unidad = DB::table('vista_unidades_extendida')->where('id', $record->pertenece_id)->first();
                        return $unidad?->nombre ?? '-';
                    }

                    if (in_array($rol, ['admin', 'gerente', 'subgerente', 'usuario'])) {
                        $gerencia = DB::table('vista_gerencias_extendida')->where('id', $record->pertenece_id)->first();
                        return $gerencia?->nombre ?? '-';
                    }

                    return '-';
                })
                ->sortable(),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            Filter::make('rol')
                ->form([
                    Select::make('value')
                        ->label('Rol')
                        ->options([
                            'administrador-unidad' => 'Administrador de Unidad',
                            'gerente' => 'Gerente',
                            'subgerente' => 'Subgerente',
                            'usuario' => 'Usuario',
                        ])
                        ->placeholder('Selecciona un rol'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when($data['value'], function ($query, $value) {
                        return $query->whereHas('roles', fn($q) => $q->where('name', $value));
                    });
                })
                ->indicateUsing(function (array $data): ?string {
                    if (!$data['value']) {
                        return null;
                    }
                    return 'Rol: ' . ucfirst($data['value']);
                }),
        ];
    }

    public function getTableActions(): array
    {
        return [
            Action::make('gestionar')
                ->label(function (User $record) {
                    $rol = $record->getRoleNames()->first();
                    return match ($rol) {
                        'administrador-unidad' => 'Editar Unidad',
                        'gerente' => 'Editar Gerencia',
                        'subgerente' => 'Editar Subgerente',
                        'usuario' => 'Editar Usuario',
                        default => 'Gestionar',
                    };
                })
                ->url(function (User $record) {
                    $rol = $record->roles->pluck('name')->first();
                    return match ($rol) {
                        'administrador-unidad' => route('filament.admin.pages.gestionar-unidades', ['user' => $record->id]),
                        'gerente' => route('filament.admin.pages.gestionar-gerentes', ['user' => $record->id]),
                        'subgerente' => route('filament.admin.pages.gestionar-gerentes', ['user' => $record->id]),
                        'usuario' => route('filament.admin.pages.gestionar-usuarios', ['user' => $record->id]),
                        default => null,
                    };
                })
                ->icon('heroicon-o-pencil-square')
                ->visible(fn(User $record) => in_array($record->roles->pluck('name')->first(), ['administrador-unidad', 'gerente', 'subgerente', 'usuario'])),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']);
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']);
    }
}
