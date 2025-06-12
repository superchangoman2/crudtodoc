<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActividadResource\Pages;
use App\Filament\Resources\ActividadResource\RelationManagers;
use App\Models\Actividad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class ActividadResource extends Resource
{
    protected static ?string $model = Actividad::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    ->required(),

                Select::make('user_id')
                    ->label('Usuario Responsable')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
