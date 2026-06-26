<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Jangan hapus diri sendiri.
                ->visible(fn () => $this->record->id !== auth()->id()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Muat peran saat ini ke field 'role'.
        $data['role'] = $this->record->roles->pluck('name')->first();

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->data['role'] ?? null;

        if ($role) {
            $this->record->syncRoles([$role]);

            if ($role !== 'vendor') {
                $this->record->update(['vendor_id' => null]);
            }
        }
    }
}
