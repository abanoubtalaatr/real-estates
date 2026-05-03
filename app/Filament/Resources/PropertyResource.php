<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Agent;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('assigned_agent_id')
                    ->label('Agent')
                    ->options(fn (): array => Agent::query()
                        ->with('user')
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (Agent $agent): array => [
                            $agent->id => $agent->user?->name ?? ('Agent #'.$agent->id),
                        ])
                        ->all())
                    ->searchable(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->maxLength(255)
                    ->helperText('Leave blank to auto-generate.'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$'),
                Forms\Components\Select::make('listing_type')
                    ->options([
                        'sale' => 'Sale',
                        'rent' => 'Rent',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('bedrooms')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('bathrooms')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('kitchens')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_featured')
                    ->inline(false),
                Forms\Components\TextInput::make('sales_count')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('latitude')
                    ->numeric()
                    ->label('Latitude (lat)'),
                Forms\Components\TextInput::make('longitude')
                    ->numeric()
                    ->label('Longitude (lng)'),
                Forms\Components\TextInput::make('address')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignedAgent.user.name')
                    ->label('Agent')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('listing_type'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sales_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Lat')
                    ->numeric(decimalPlaces: 5)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('longitude')
                    ->label('Lng')
                    ->numeric(decimalPlaces: 5)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProperties::route('/'),
        ];
    }
}
