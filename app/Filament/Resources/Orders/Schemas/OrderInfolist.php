<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Order')
                    ->columns(3)
                    ->components([
                        TextEntry::make('order_number')->label('No. Order'),
                        TextEntry::make('status')
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
                        TextEntry::make('payment_method')
                            ->label('Metode Bayar')
                            ->formatStateUsing(fn (?string $state) => $state ? strtoupper($state) : '—'),
                        TextEntry::make('table_number')->label('Meja')->placeholder('—'),
                        TextEntry::make('floor')
                            ->label('Lantai')
                            ->placeholder('—')
                            ->visible(fn ($record) => filled($record->floor)),
                        TextEntry::make('cashier.name')->label('Kasir')->placeholder('—'),
                        TextEntry::make('location.name')->label('Lokasi'),
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                        TextEntry::make('discount_amount')
                            ->label('Diskon')
                            ->money('IDR', 0)
                            ->placeholder('—')
                            ->visible(fn ($record) => (int) $record->discount_amount > 0),
                        TextEntry::make('shipping_cost')
                            ->label('Ongkir (kurir)')
                            ->money('IDR', 0)
                            ->placeholder('—')
                            ->helperText('Catatan nota, hak kurir — di luar total.')
                            ->visible(fn ($record) => (int) $record->shipping_cost > 0),
                        TextEntry::make('total_amount')->label('Total')->money('IDR', 0),
                        TextEntry::make('paid_amount')->label('Dibayar')->money('IDR', 0),
                        TextEntry::make('change_amount')->label('Kembalian')->money('IDR', 0),
                    ]),
                Section::make('Item Pesanan')
                    ->components([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->components([
                                TextEntry::make('name_snapshot')->label('Menu'),
                                TextEntry::make('vendor.code')->label('Vendor')->badge(),
                                TextEntry::make('qty')->label('Qty'),
                                TextEntry::make('line_total')->label('Subtotal')->money('IDR', 0),
                            ]),
                    ]),
            ]);
    }
}
