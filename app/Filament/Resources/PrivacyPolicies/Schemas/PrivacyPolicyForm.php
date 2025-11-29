<?php

namespace App\Filament\Resources\PrivacyPolicies\Schemas;

use App\Models\PrivacyPolicy;
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
                    ->numeric()
                    ->default(fn () => PrivacyPolicy::max('order') + 1)
                    ->required(),
            ]);
    }
}
