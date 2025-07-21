<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoPuestoResource\Pages;
use App\Filament\Resources\TipoPuestoResource\RelationManagers;
use App\Models\TipoPuesto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;

use Filament\Tables\Columns\TextColumn;
class TipoPuestoResource extends Resource
{
    protected static ?string $model = TipoPuesto::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Campo oculto para el usuario que crea el registro
                Hidden::make('id_usuario')
                    ->default(fn () => Auth::id()),

                // Campo para el nombre del puesto
                TextInput::make('nombre')
                    ->label('Nombre del Puesto')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->sortable(),


                TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Puedes agregar filtros aquí si deseas
            ])  ->extraAttributes(['autocomplete' => 'off']);
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
            'index' => Pages\ListTipoPuestos::route('/'),
            'create' => Pages\CreateTipoPuesto::route('/create'),
            'edit' => Pages\EditTipoPuesto::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('id_usuario', auth()->id());
    }
}
