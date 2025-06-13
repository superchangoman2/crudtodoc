<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActividadResource\Pages;
use App\Filament\Resources\ActividadResource\RelationManagers;
use App\Models\Actividad;
use App\Models\Gerencia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;



class ActividadResource extends Resource
{
    protected static ?int $navigationSort = 4;
    protected static ?string $model = Actividad::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Actividades';
    protected static ?string $pluralModelLabel = 'Actividades';
    protected static ?string $navigationGroup = 'Registros';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('admin')) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->autosize()
                    ->columnSpanFull(),

                Select::make('tipo_actividad_id')
                    ->label('Tipo de Actividad')
                    ->relationship('tipoActividad', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('gerencia_id')
                    ->label('Gerencia')
                    ->relationship('gerencia', 'nombre')
                    ->searchable()
                    ->required()
                    ->default(function () {
                        $user = auth()->user();
                        if ($user->hasRole('gerente')) {
                            return Gerencia::where('user_id', $user->id)->value('id') ?? 'NAN';
                        } elseif ($user->gerencia_id) {
                            return $user->gerencia_id;
                        }
                        return 'NAN';
                    })
                    ->disabled(function () {
                        $user = auth()->user();
                        return !$user->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
                    }),

                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required()
                    ->default(today()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name') // <- accede al nombre del usuario
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->visible(fn() => auth()->user()?->hasRole(['admin', 'administrador-unidad', 'gerente']))
                    ->formatStateUsing(function ($state, $record) {
                        return "{$record->user_id} - {$state}";
                    }),
                TextColumn::make('gerencia.nombre') // <- accede al nombre del usuario
                    ->label('Gerencia')
                    ->sortable()
                    ->searchable()
                    ->visible(fn() => auth()->user()?->hasRole(['admin', 'administrador-unidad'])),
                TextColumn::make('tipoActividad.nombre')
                    ->label('Tipo de Actividad')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('titulo')
                    ->label('Título')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fecha')
                    ->label('fecha')
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
                DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasRole('admin')),

                RestoreAction::make()
                    ->visible(fn($record) => auth()->user()?->hasRole('admin') && $record->trashed()),

                ForceDeleteAction::make()
                    ->visible(fn($record) => auth()->user()?->hasRole('admin') && $record->trashed()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('admin')),
                    RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('admin')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActividads::route('/'),
            'create' => Pages\CreateActividad::route('/create'),
            'edit' => Pages\EditActividad::route('/{record}/edit'),
        ];
    }
    public static function getModelLabel(): string
    {
        return 'actividad';
    }

    public static function getPluralModelLabel(): string
    {
        return 'actividades';
    }
}
