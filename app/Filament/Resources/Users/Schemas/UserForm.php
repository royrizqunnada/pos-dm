<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email (untuk login)')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Peran')
                    ->options([
                        'owner' => 'Owner',
                        'manager' => 'Manajer',
                        'cashier' => 'Kasir',
                        'vendor' => 'Vendor',
                    ])
                    ->required()
                    ->live()
                    ->dehydrated(false), // disinkronkan via page (spatie role)
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Akun ini hanya bisa melihat data vendor terpilih.')
                    ->visible(fn (Get $get) => $get('role') === 'vendor')
                    ->required(fn (Get $get) => $get('role') === 'vendor'),
                Select::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->default(fn () => \App\Models\Location::query()->value('id')),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText('Kosongkan jika tidak ingin mengubah (saat edit).')
                    ->maxLength(255),
            ]);
    }
}
