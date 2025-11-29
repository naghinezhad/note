<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->required(),
                TextInput::make('order')
                    ->label('جایگاه')
                    ->numeric()
                    ->default(fn () => PrivacyPolicy::max('order') + 1)
                    ->required(),
                ColorPicker::make('color')
                    ->label('رنگ')
                    ->required(),
                Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
            ]);
    }
}
