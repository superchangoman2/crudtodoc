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
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['administrador-unidad', 'gerente', 'subgerente', 'usuario']))
                ->where('id', '<>', $authUser->id)
                ->with(['roles']);
        }

        if ($authUser->hasRole('administrador-unidad')) {
            $unidadId = $authUser->pertenece_id;

            $gerenciasIds = Gerencia::where('unidad_administrativa_id', $unidadId)->pluck('id');

            return User::query()
                ->where('id', '<>', $authUser->id)
                ->where(function ($query) use ($unidadId, $gerenciasIds) {
                    $query->where(function ($q) use ($unidadId) {
                        $q->where('pertenece_id', $unidadId)
                            ->whereHas('roles', fn($r) => $r->where('name', 'administrador-unidad'));
                    })
                        ->orWhere(function ($q) use ($gerenciasIds) {
                            $q->whereIn('pertenece_id', $gerenciasIds)
                                ->whereHas('roles', fn($r) => $r->whereIn('name', ['gerente', 'subgerente', 'usuario']));
                        });
                })
                ->with(['roles']);
        }

        if ($authUser->hasRole('gerente')) {
            $gerenciaId = $authUser->pertenece_id;

            return User::query()
                ->where('id', '<>', $authUser->id)
                ->where('pertenece_id', $gerenciaId)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['subgerente', 'usuario']))
                ->with(['roles']);
        }

        if ($authUser->hasRole('subgerente')) {
            $gerenciaId = $authUser->pertenece_id;

            return User::query()
                ->where('id', '<>', $authUser->id)
                ->where('pertenece_id', $gerenciaId)
                ->whereHas('roles', fn($q) => $q->where('name', 'usuario'))
                ->with(['roles']);
        }

        return User::query()->whereRaw('0 = 1');
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('name')->label('Nombre')->sortable()->searchable(),
            TextColumn::make('email')->label('Correo')->sortable()->searchable(),
            TextColumn::make('roles.name')
                ->label('Rol')
                ->sortable()
                ->searchable()
                ->formatStateUsing(fn($state) => ucfirst($state)),
            TextColumn::make('pertenencia')
                ->label('Pertenencia')
                ->formatStateUsing(function ($state, $record) {
                    return match ($record->pertenencia_tipo) {
                        'unidad' => UnidadAdministrativa::find($record->pertenece_id)?->nombre ?? '—',
                        'gerencia' => Gerencia::find($record->pertenece_id)?->nombre ?? '—',
                        default => '—',
                    };
                })
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
                ->label(fn(User $record) => match ($record->roles->pluck('name')->first()) {
                    'administrador-unidad' => 'Editar Unidad',
                    'gerente' => 'Editar Gerencia',
                    'subgerente' => 'Editar Subgerente',
                    'usuario' => 'Editar Usuario',
                    default => 'Gestionar',
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
