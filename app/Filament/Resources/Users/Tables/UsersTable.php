<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'owner' => 'Owner',
                        'manager' => 'Manajer',
                        'cashier' => 'Kasir',
                        'vendor' => 'Vendor',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'owner' => 'danger',
                        'manager' => 'warning',
                        'cashier' => 'info',
                        'vendor' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name')
                    ->multiple(),
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
