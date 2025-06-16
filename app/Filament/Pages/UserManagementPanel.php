<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Contracts\HasTable;

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

        if ($authUser->hasRole('admin')) {
            return User::query()
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['administrador-unidad', 'gerente', 'usuario']))
                ->with(['roles', 'gerencia', 'unidadAdministrativa']);
        }

        if ($authUser->hasRole('administrador-unidad')) {
            $unidadId = $authUser->unidadAdministrativa?->id;

            return User::query()
                ->where(function ($query) use ($unidadId) {
                    // Gerentes con gerencia en su unidad o sin gerencia
                    $query->where(function ($q) use ($unidadId) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'gerente'))
                            ->where(function ($g) use ($unidadId) {
                                $g->whereHas('gerenciaQueDirige', fn($h) => $h->where('unidad_administrativa_id', $unidadId))
                                    ->orWhereDoesntHave('gerenciaQueDirige');
                            });
                    })
                        // Usuarios con gerencia en su unidad o sin gerencia
                        ->orWhere(function ($q) use ($unidadId) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'usuario'))
                            ->where(function ($u) use ($unidadId) {
                                $u->whereHas('gerencia', fn($g) => $g->where('unidad_administrativa_id', $unidadId))
                                    ->orWhereNull('gerencia_id');
                            });
                    });
                })
                ->with(['roles', 'gerencia', 'unidadAdministrativa']);
        }

        if ($authUser->hasRole('gerente')) {
            $gerenciaId = $authUser->gerenciaQueDirige?->id;

            return User::query()
                ->whereHas('roles', fn($q) => $q->where('name', 'usuario'))
                ->where(function ($q) use ($gerenciaId) {
                    $q->where('gerencia_id', $gerenciaId)
                        ->orWhereNull('gerencia_id');
                })
                ->with(['roles', 'gerencia', 'unidadAdministrativa']);
        }

        return User::query()->whereRaw('0 = 1'); // No accede si no tiene rol permitido
    }


    public function getTableColumns(): array
    {
        return [
            TextColumn::make('name')->label('Nombre')
                ->sortable()
                ->searchable(),
            TextColumn::make('email')->label('Correo')
                ->sortable()
                ->searchable(),
            TextColumn::make('roles.name')
                ->label('Rol')
                ->sortable()
                ->searchable()
                ->formatStateUsing(fn($state) => ucfirst($state)),

            TextColumn::make('gerencia.nombre')
                ->label('Gerencia')
                ->searchable()
                ->visible(function () {
                    $usuario = auth()->user();
                    return $usuario && $usuario->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
                }),

            TextColumn::make('gerenciaQueDirige.nombre')
                ->label('Gerencia que dirige')
                ->visible(function () {
                    $usuario = auth()->user();
                    return $usuario && $usuario->hasAnyRole(['admin', 'administrador-unidad']);
                }),

            TextColumn::make('unidadAdministrativa.nombre')
                ->label('Unidad Administrativa')
                ->searchable()
                ->visible(function () {
                    $usuario = auth()->user();
                    return $usuario && $usuario->hasAnyRole(['admin']);
                }),
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

                ->label(fn(User $record) => match ($record->roles->pluck('name')->first()) {
                    'administrador-unidad' => 'Editar Unidad',
                    'gerente' => 'Editar Gerencia',
                    'usuario' => 'Editar Usuario',
                    default => 'Gestionar',
                })

                ->url(function (User $record) {
                    $rol = $record->roles->pluck('name')->first();
                    return match ($rol) {
                        'administrador-unidad' => route('filament.admin.pages.gestionar-unidades', ['user' => $record->id]),
                        'gerente' => route('filament.admin.pages.gestionar-gerentes', ['user' => $record->id]),
                        'usuario' => route('filament.admin.pages.gestionar-usuarios', ['user' => $record->id]),
                        default => null,
                    };
                })

                ->icon('heroicon-o-pencil-square')
                ->extraAttributes(['class' => 'justify-start w-full'])
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
