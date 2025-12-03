<?php

namespace App\Filament\Resources\CoinPackages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
                RichEditor::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('عکس')
                    ->image(),
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
                TextInput::make('link_cafebazaar')
                    ->label('لینک بازار'),
                TextInput::make('link_myket')
                    ->label('لینک مایکت'),
                Toggle::make('is_active')
                    ->label('وضعیت')
                    ->required()
                    ->default(true),
            ]);
    }
}
