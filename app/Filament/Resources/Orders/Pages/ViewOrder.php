<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('void')
                ->label('Batalkan (Void)')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Order yang dibatalkan tidak dihitung dalam settlement. Lanjutkan?')
                ->visible(fn (Order $record) => $record->status !== 'void'
                    && auth()->user()?->hasAnyRole(['owner', 'manager']))
                ->action(function (Order $record) {
                    $record->update([
                        'status' => 'void',
                        'voided_at' => now(),
                    ]);
                }),
        ];
    }
}
