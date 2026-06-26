<?php

namespace App\Filament\Resources\Vendors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Vendor')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->toggleable(),
                TextColumn::make('menu_items_count')
                    ->label('Jml Menu')
                    ->counts('menuItems')
                    ->badge(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->toggleable(),
                TextColumn::make('payout_account')
                    ->label('Rekening')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
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
