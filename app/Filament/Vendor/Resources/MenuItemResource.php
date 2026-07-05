<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\MenuItemResource\Pages\ListMenuItems;
use App\Models\MenuItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $pluralModelLabel = 'Menu Saya';

    protected static ?int $navigationSort = 2;

    /**
     * Vendor hanya melihat menu miliknya sendiri.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->user()?->vendor_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Menu')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('base_price')
                    ->label('Jatah Saya')
                    ->money('IDR', 0)
                    ->sortable(),
                ToggleColumn::make('is_available')
                    ->label('Tersedia')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuItems::route('/'),
        ];
    }
}
