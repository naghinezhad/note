<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام'),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true),
                DateTimePicker::make('email_verified_at')
                    ->label('تاریخ تأیید ایمیل'),
                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->revealable(),
            ]);
    }
}
