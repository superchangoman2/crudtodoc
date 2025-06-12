<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class UserAdminPanel extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Gestión de Usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $view = 'filament.pages.user-admin-panel';
    protected static ?string $title = 'Gestión de Usuarios';

    public function getTableQuery(): Builder
    {
        return User::query()->with(['roles', 'gerencia', 'unidadAdministrativa']);
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

            TextColumn::make('unidadAdministrativa.nombre')
                ->label('Unidad')
                ->searchable(),

            TextColumn::make('gerencia.nombre')
                ->label('Gerencia')
                ->searchable(),
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
                            'admin' => 'Administrador',
                            'administrador-unidad' => 'Administrador de Unidad',
                            'gerente' => 'Gerente',
                            'usuario' => 'Usuario',
                        ])
                        ->placeholder('Selecciona un rol')
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when($data['value'], function ($query, $value) {
                        return $query->whereHas('roles', fn($q) => $q->where('name', $value));
                    });
                })
                ->indicateUsing(function (array $data): ?string {
                    if (!$data['value'])
                        return null;

                    return 'Rol: ' . ucfirst($data['value']);
                }),
        ];
    }
    public function getTableActions(): array
    {
        return [
            Action::make('gestionar')
                ->label('Gestionar')
                ->url(fn(User $record) => route('filament.admin.pages.administrar-usuarios', ['user' => $record->id]))
                ->icon('heroicon-o-cog-6-tooth'),
        ];
    }
}
