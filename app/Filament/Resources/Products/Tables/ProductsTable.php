<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->searchable(),
                ImageColumn::make('high_quality_image')
                    ->label('عکس با کیفیت'),
                ImageColumn::make('low_quality_image')
                    ->label('عکس بی کیفیت'),
                TextColumn::make('price')
                    ->label('قیمت')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state).' تومان'),
                TextColumn::make('description')
                    ->label('توضیحات'),
                TextColumn::make('likes')
                    ->label('تعداد لایک')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('views')
                    ->label('تعداد بازدید')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchased')
                    ->label('تعداد خریداری شده')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('دسته بندی')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean(),
                IconColumn::make('is_3d')
                    ->label('3d')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->jalaliDateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('تاریخ آپدیت')
                    ->dateTime()
                    ->jalaliDateTime()
                    ->sortable(),
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
