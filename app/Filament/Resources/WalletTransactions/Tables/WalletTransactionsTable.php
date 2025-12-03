<?php

namespace App\Filament\Resources\WalletTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('wallet.user.email')
                    ->label('کیف پول')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'purchase_product' => 'خرید کتاب',
                            'purchase_package' => 'خرید پکیج',
                            default => $state,
                        };
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('coins')
                    ->label('تعداد کوین')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('coins_before')
                    ->label('قبل تعداد کوین')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('coins_after')
                    ->label('بعد تعداد کوین')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('paid_amount')
                    ->label('مبلغ پرداختی')
                    ->numeric()
                    ->money('IRR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('توضیحات')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('product.name')
                    ->label('نام محصول')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('coinPackage.name')
                    ->label('نام پکیح')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reference_code')
                    ->label('کد پیگیری')
                    ->searchable()
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
