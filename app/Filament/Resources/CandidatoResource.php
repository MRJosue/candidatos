<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidatoResource\Pages;
use App\Filament\Resources\CandidatoResource\RelationManagers;
use App\Models\Candidato;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;


class CandidatoResource extends Resource
{
    protected static ?string $model = Candidato::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
    return $form
        ->schema([
            Hidden::make('id_usuario')
                ->default(fn () => Auth::id()),

            Select::make('tipo_puesto_id')
                ->label('Tipo de Puesto')
                ->relationship(
                    name: 'tipoPuesto',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn ($query) => $query->where('id_usuario', auth()->id())
                )
                ->required(),
            TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            TextInput::make('correo')
                ->label('Correo electrónico')
                ->email()
                ->required()
                ->maxLength(255),

            TextInput::make('telefono')
                ->label('Teléfono')
                ->required()
                ->maxLength(20),

            DatePicker::make('fecha_postulacion')
                ->label('Fecha de Postulación')
                ->required(),

            Textarea::make('comentarios')
                ->label('Comentarios')
                ->rows(3),
        ])  ->extraAttributes(['autocomplete' => 'off']);
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

                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable(),

                TextColumn::make('telefono')
                    ->label('Teléfono'),

                TextColumn::make('tipoPuesto.nombre')
                    ->label('Tipo de Puesto')
                    ->sortable(),

                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->sortable(),

                TextColumn::make('fecha_postulacion')
                    ->label('Fecha de Postulación')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Puedes agregar filtros aquí si deseas
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
            'index' => Pages\ListCandidatos::route('/'),
            'create' => Pages\CreateCandidato::route('/create'),
            'edit' => Pages\EditCandidato::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()
                ->where('id_usuario', auth()->id());
        }
}
