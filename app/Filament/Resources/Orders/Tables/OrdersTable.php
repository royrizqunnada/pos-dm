<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Order')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'paid' => 'Lunas',
                        'void' => 'Batal',
                        default => 'Terbuka',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'void' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? strtoupper($state) : '—'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR', 0)
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Terbuka',
                        'paid' => 'Lunas',
                        'void' => 'Batal',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Metode')
                    ->options([
                        'cash' => 'Tunai',
                        'qris' => 'QRIS',
                    ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
