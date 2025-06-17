<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Select, DatePicker};

use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Actions\{
    EditAction,
    DeleteAction,
    RestoreAction,
    ForceDeleteAction
};

use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Base de datos';
    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->withTrashed();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->label('Nombre')
                    ->required(),

                TextInput::make('last_name')
                    ->label('Apellido')
                    ->required(),

                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->maxLength(255)
                    ->required(fn(string $context) => $context === 'create')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->visible(fn(string $context) => $context === 'create'),

                Select::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple(false)
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Registrado el')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),

                Filter::make('trashed')
                    ->label('Registros eliminados')
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->form([
                        Select::make('estado')
                            ->label('Mostrar')
                            ->options([
                                'activos' => 'Solo activos',
                                'eliminados' => 'Solo eliminados',
                                'todos' => 'Todos',
                            ])
                            ->default('activos'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['estado'] ?? 'activos') {
                            'eliminados' => $query->onlyTrashed(),
                            'todos' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),

                RestoreAction::make()
                    ->visible(fn($record) => $record->trashed()),

                ForceDeleteAction::make()
                    ->visible(fn($record) => $record->trashed()),
            ], position: ActionsPosition::BeforeColumns);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }
}
