<?php

namespace App\Filament\Resources\Otps\Pages;

use App\Filament\Resources\Otps\OtpResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOtps extends ListRecords
{
    protected static string $resource = OtpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
