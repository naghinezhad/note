<?php

namespace App\Filament\Resources\Otps\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OtpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('آدرس ایمیل')
                    ->email()
                    ->required()
                    ->placeholder('example@domain.com'),
                TextInput::make('code')
                    ->label('کد OTP')
                    ->required()
                    ->placeholder('کد تأیید را وارد کنید')
                    ->maxLength(6),
                DateTimePicker::make('expires_at')
                    ->label('تاریخ انقضا')
                    ->required()
                    ->jalali(weekdaysShort: true)
                    ->minDate(now())
                    ->helperText('تاریخ انقضای کد تأیید را انتخاب کنید'),
            ]);
    }
}
