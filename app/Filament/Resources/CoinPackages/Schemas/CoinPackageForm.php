<?php

namespace App\Filament\Resources\CoinPackages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CoinPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام پکیج')
                    ->required(),
                TextInput::make('coins')
                    ->label('تعداد کوین')
                    ->required()
                    ->numeric(),
                TextInput::make('price')
                    ->label('قیمت')
                    ->required()
                    ->numeric()
                    ->prefix('ریال'),
                TextInput::make('discount_percentage')
                    ->label('درصد تخفیف')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('وضعیت')
                    ->required()
                    ->default(true),
            ]);
    }
}
