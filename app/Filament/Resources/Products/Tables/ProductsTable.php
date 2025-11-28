<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('high_quality_image')
                    ->label('عکس با کیفیت')
                    ->toggleable(),
                ImageColumn::make('low_quality_image')
                    ->label('عکس بی کیفیت')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('قیمت')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state).' کوین')
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('توضیحات')
                    ->toggleable(),
                TextColumn::make('likes')
                    ->label('تعداد لایک')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('views')
                    ->label('تعداد بازدید')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('purchased')
                    ->label('تعداد خریداری شده')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label('دسته بندی')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                ColorColumn::make('category.color')
                    ->label('رنگ دسته بندی')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_3d')
                    ->label('3d')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('تاریخ آپدیت')
                    ->dateTime()
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
