<?php

namespace App\Filament\Resources\MenuItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('vendor.name')
                    ->label('Vendor')
                    ->collapsible(),
            ])
            ->defaultGroup('vendor.name')
            ->paginationPageOptions([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Menu')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('base_price')
                    ->label('Harga Dasar')
                    ->money('IDR', 0)
                    ->sortable(),
                TextColumn::make('margin')
                    ->label('Margin')
                    ->money('IDR', 0)
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR', 0)
                    ->weight('bold')
                    ->sortable(),
                ToggleColumn::make('is_available')
                    ->label('Tersedia')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->filters([
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name'),
                TernaryFilter::make('is_available')
                    ->label('Ketersediaan'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
