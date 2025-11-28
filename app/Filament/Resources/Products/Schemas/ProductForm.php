<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام'),
                TextInput::make('price')
                    ->label('قیمت')
                    ->required()
                    ->numeric()
                    ->prefix('کوین'),
                FileUpload::make('high_quality_image')
                    ->label('عکس با کیفیت')
                    ->image()
                    ->required(),
                FileUpload::make('low_quality_image')
                    ->label('عکس بی کیفیت')
                    ->image()
                    ->required(),
                Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                TextInput::make('likes')
                    ->label('تعداد لایک')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly(),
                TextInput::make('views')
                    ->label('تعداد بازدید')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly(),
                TextInput::make('purchased')
                    ->label('تعداد خریداری شده')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly(),
                Select::make('category_id')
                    ->label('دسته بندی')
                    ->required()
                    ->options(Category::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->placeholder('یک دسته بندی انتخاب کنید'),
                Toggle::make('is_active')
                    ->label('وضعیت')
                    ->required()
                    ->default(true),
                Toggle::make('is_3d')
                    ->label('3d')
                    ->required(),
            ]);
    }
}
