<?php

namespace App\Filament\Resources\CoinPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoinPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام پکیج')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('توضیحات')
                    ->html(),
                ImageColumn::make('image')
                    ->label('عکس'),
                TextColumn::make('coins')
                    ->label('تعداد کوین')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('قیمت')
                    ->money('IRR')
                    ->sortable(),
                TextColumn::make('discount_percentage')
                    ->label('درصد تخفیف')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('link_cafebazaar')
                    ->label('لینک کافه بازار')
                    ->url(fn ($record) => $record->link_cafebazaar)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->limit(30),
                TextColumn::make('link_myket')
                    ->label('لینک مایکت')
                    ->url(fn ($record) => $record->link_myket)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->limit(30),
                IconColumn::make('is_active')
                    ->label('وضعیت')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
