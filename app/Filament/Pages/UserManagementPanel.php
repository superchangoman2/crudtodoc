<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class UserManagementPanel extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Gestión de Usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $view = 'filament.pages.user-management-panel';
    protected static ?string $title = 'Gestión de Usuarios';

    public function getTableQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', fn($query) => $query->where('name', 'usuario'))
            ->with(['roles', 'gerencia']);
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
                ->searchable(),
        ];
    }

    public function getTableActions(): array
    {
        return [
            Action::make('gestionar')
                ->label('Gestionar')
                ->url(fn(User $record) => route('filament.admin.pages.gestionar-usuarios', ['user' => $record->id]))
                ->icon('heroicon-o-cog-6-tooth'),
        ];
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }
}
