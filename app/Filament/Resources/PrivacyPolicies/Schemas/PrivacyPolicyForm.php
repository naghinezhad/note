<?php

namespace App\Filament\Resources\PrivacyPolicies\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PrivacyPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('title')
                    ->label('عنوان')
                    ->required()
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->label('محتوا')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('order')
                    ->label('جایگاه')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
