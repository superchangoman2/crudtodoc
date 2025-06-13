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

class AdminManagementPanel extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Gestión Administrativa';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $title = 'Gestión Administrativa';
    protected static string $view = 'filament.pages.admin-management-panel';

    public function getTableQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrador-unidad', 'gerente']);

                // Si el usuario autenticado es gerente, ocultar a los gerentes
                if (auth()->user()->hasRole('gerente')) {
                    $query->where('name', '!=', 'gerente');
                }
            })
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
                ->label(fn(User $record) => match ($record->roles->pluck('name')->first()) {
                    'gerente' => 'Editar Gerencia',
                    'administrador-unidad' => 'Editar Unidad',
                    default => 'Gestionar',
                })
                ->url(function (User $record) {
                    $rol = $record->roles->pluck('name')->first();

                    return match ($rol) {
                        'gerente' => route('filament.admin.pages.gestionar-gerentes', ['user' => $record->id]),
                        'administrador-unidad' => route('filament.admin.pages.gestionar-unidades', ['user' => $record->id]),
                        default => null,
                    };
                })
                ->icon('heroicon-o-pencil-square')
                ->extraAttributes(['class' => 'justify-start w-full'])
                ->visible(fn(User $record) => in_array($record->roles->pluck('name')->first(), ['gerente', 'administrador-unidad'])),
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
