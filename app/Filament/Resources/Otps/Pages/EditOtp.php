<?php

namespace App\Filament\Resources\Otps\Pages;

use App\Filament\Resources\Otps\OtpResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOtp extends EditRecord
{
    protected static string $resource = OtpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
