<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Restaurant;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RestaurantResource\Pages;
use App\Filament\Resources\RestaurantResource\RelationManagers;
use Filament\Tables\Columns\TextColumn;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Restoran')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email Restoran')
                    ->required()
                    ->email()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label('Alamat Restoran')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('No Telepon Restoran')
                    ->tel()
                    ->maxLength(30)
                    ->nullable(),
                Textarea::make('description')
                    ->label('Deskripsi Restoran')
                    ->nullable(),
                FileUpload::make('logo')
                    ->label('Logo Restoran')
                    ->image()
                    ->nullable()
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory('restaurant_logos')
                    ->visibility('public'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Restoran')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email Restoran')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No Telepon Restoran')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i:s')
                    ->searchable(),
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
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
