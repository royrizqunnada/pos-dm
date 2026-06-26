<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->default(fn () => \App\Models\Location::query()->value('id'))
                    ->required(),
                TextInput::make('code')
                    ->label('Kode Vendor')
                    ->helperText('Singkatan unik per lokasi, mis. BB / JD / MNL')
                    ->required()
                    ->maxLength(20),
                TextInput::make('name')
                    ->label('Nama Vendor')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel()
                    ->maxLength(30),
                TextInput::make('payout_account')
                    ->label('Rekening Pembayaran')
                    ->helperText('Tujuan transfer settlement, mis. BCA 1234567890 a.n. ...')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
