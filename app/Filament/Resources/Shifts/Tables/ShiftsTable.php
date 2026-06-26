<?php

namespace App\Filament\Resources\Shifts\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('opened_at')
                    ->label('Buka')
                    ->dateTime('d M H:i')
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label('Tutup')
                    ->dateTime('d M H:i')
                    ->placeholder('— berjalan —'),
                TextColumn::make('total_cash_sales')
                    ->label('Tunai')
                    ->money('IDR', 0),
                TextColumn::make('total_qris_sales')
                    ->label('QRIS')
                    ->money('IDR', 0),
                TextColumn::make('expected_cash')
                    ->label('Kas Seharusnya')
                    ->money('IDR', 0)
                    ->toggleable(),
                TextColumn::make('counted_cash')
                    ->label('Kas Fisik')
                    ->money('IDR', 0)
                    ->toggleable(),
                TextColumn::make('cash_variance')
                    ->label('Selisih')
                    ->money('IDR', 0)
                    ->color(fn ($state) => $state == 0 ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('order_count')
                    ->label('Transaksi')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'open' ? 'Berjalan' : 'Selesai')
                    ->color(fn (string $state) => $state === 'open' ? 'warning' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['open' => 'Berjalan', 'closed' => 'Selesai']),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
