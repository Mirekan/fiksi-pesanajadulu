<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Menu')
                    ->required()
                    ->maxLength(255),
                Select::make('category')
                    ->label('Kategori Menu')
                    ->searchable()
                    ->options([
                        'appetizer' => 'Appetizer',
                        'main_course' => 'Main Course',
                        'dessert' => 'Dessert',
                        'beverage' => 'Beverage',
                    ])
                    ->required(),
                TextInput::make('price')
                    ->label('Harga Menu')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('Rp')
                    ->required(),
                Select::make('restaurant_id')
                    ->label('Restoran')
                    ->relationship('restaurant', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Restoran'),
                Textarea::make('description')
                    ->label('Deskripsi Menu')
                    ->nullable(),
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
                    ->label('Nama Menu')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Harga'),
                TextColumn::make('restaurant.name')
                    ->label('Restoran')
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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
