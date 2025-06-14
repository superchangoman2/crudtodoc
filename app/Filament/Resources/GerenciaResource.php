<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GerenciaResource\Pages;
use App\Filament\Resources\GerenciaResource\RelationManagers;
use App\Models\Gerencia;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
class GerenciaResource extends Resource
{
    protected static ?string $model = Gerencia::class;

    protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Gerencias';
    protected static ?string $pluralModelLabel = 'Gerencias';
    protected static ?string $navigationGroup = 'Base de datos';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->label('Nombre de la Gerencia')
                    ->placeholder('Ej: Gerencia de Tecnología de la Información y Comunicación')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255)
                    ->rule('string')
                    ->unique(ignoreRecord: true),
                Select::make('unidad_administrativa_id')
                    ->label('Unidad Administrativa')
                    ->relationship('unidadAdministrativa', 'nombre')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('user_id')
                    ->label('Responsable (Gerente)')
                    ->options(function () {
                        $gerentesDisponibles = User::whereHas('roles', fn($q) => $q->where('name', 'gerente'))
                            ->whereDoesntHave('gerenciaQueDirige')
                            ->get();

                        $actual = null;
                        if ($record = request()->route('record')) {
                            $actual = Gerencia::find($record)?->gerente;
                        }

                        $gerentes = $actual
                            ? $gerentesDisponibles->push($actual)->unique('id')
                            : $gerentesDisponibles;

                        return $gerentes->mapWithKeys(fn($user) => [
                            $user->id => "{$user->id} – {$user->name} – {$user->email}"
                        ]);
                    })
                    ->searchable()
                    ->nullable()
                    ->helperText('Selecciona un gerente sin gerencia asignada')
                    ->rules([
                        'nullable',
                        'unique:gerencias,user_id' . (request()->route('record') ? ',' . request()->route('record') : ''),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Registrado el')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('unidadAdministrativa.nombre')
                    ->label('Unidad Administrativa')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label('#Usuarios')
                    ->sortable()
                    ->counts('users'),
                TextColumn::make('gerente.email')
                    ->label('Correo del responsable')
                    ->sortable()
                    ->searchable(),
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
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGerencias::route('/'),
            'create' => Pages\CreateGerencia::route('/create'),
            'edit' => Pages\EditGerencia::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'gerencia';
    }

    public static function getPluralModelLabel(): string
    {
        return 'gerencias';
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

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }
}
