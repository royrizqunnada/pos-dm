<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Lokasi')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),

                Section::make('Tampilan Struk')
                    ->description('Diatur di sini, langsung tampil di struk pelanggan.')
                    ->columns(2)
                    ->components([
                        TextInput::make('receipt_name')
                            ->label('Nama di Header Struk')
                            ->placeholder('DM KULINER')
                            ->helperText('Tulisan besar paling atas struk.')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->placeholder('0812-xxxx-xxxx')
                            ->maxLength(50),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->placeholder('@dmkuliner.id')
                            ->maxLength(255),
                        TextInput::make('receipt_footer')
                            ->label('Ucapan Footer')
                            ->placeholder('Terima kasih & selamat menikmati!')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
