<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $role = $this->data['role'] ?? null;

        if ($role) {
            $this->record->syncRoles([$role]);

            // Vendor wajib punya vendor_id; selain vendor, kosongkan.
            if ($role !== 'vendor') {
                $this->record->update(['vendor_id' => null]);
            }
        }
    }
}
