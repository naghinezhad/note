<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function ($record, $action) {

                    if (DB::table('sessions')->where('user_id', $record->id)->exists()) {

                        Notification::make()
                            ->danger()
                            ->title('خطا در حذف')
                            ->body('این کاربر در حال حاضر سشن فعال دارد و قابل حذف نیست.')
                            ->send();

                        $action->cancel();

                        return;
                    }

                    $record->delete();

                }),
        ];
    }
}
