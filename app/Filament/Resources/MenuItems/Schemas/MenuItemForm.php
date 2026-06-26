<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        // selling_price = base_price + margin, dihitung live di form.
        $recalc = function (Get $get, Set $set): void {
            $set('selling_price', (int) $get('base_price') + (int) $get('margin'));
        };

        return $schema
            ->components([
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Menu')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['md' => 1]),
                TextInput::make('category')
                    ->label('Kategori')
                    ->datalist(fn () => \App\Models\MenuItem::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category')
                        ->all())
                    ->maxLength(255),
                TextInput::make('base_price')
                    ->label('Harga Dasar (jatah vendor)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated($recalc),
                TextInput::make('margin')
                    ->label('Margin (jatah owner)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated($recalc),
                TextInput::make('selling_price')
                    ->label('Harga Jual (dibayar pelanggan)')
                    ->helperText('Otomatis = harga dasar + margin')
                    ->numeric()
                    ->prefix('Rp')
                    ->readOnly()
                    ->dehydrated(false), // model menghitung ulang saat simpan
                Toggle::make('is_available')
                    ->label('Tersedia')
                    ->default(true),
                TextInput::make('note')
                    ->label('Catatan')
                    ->maxLength(255)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Foto Menu')
                    ->image()
                    ->directory('menu-images')
                    ->columnSpanFull(),
            ]);
    }
}
