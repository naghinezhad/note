<?php

namespace App\Filament\Resources\WalletTransactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WalletTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('wallet_id')
                    ->label('کیف پول')
                    ->relationship('wallet', 'id')
                    ->required(),
                TextInput::make('type')
                    ->label('نوع')
                    ->required(),
                TextInput::make('coins')
                    ->label('تعداد کوین')
                    ->required()
                    ->numeric(),
                TextInput::make('coins_before')
                    ->label('قبل تعداد کوین')
                    ->required()
                    ->numeric(),
                TextInput::make('coins_after')
                    ->label('بعد تعداد کوین')
                    ->required()
                    ->numeric(),
                TextInput::make('paid_amount')
                    ->label('مبلغ پرداختی')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('ریال'),
                TextInput::make('description')
                    ->label('توضیحات'),
                Select::make('product_id')
                    ->label('نام محصول')
                    ->relationship('product', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->name ?: 'بدون نام').' - ID: '.$record->id
                    )->searchable(['name', 'id']),
                Select::make('coin_package_id')
                    ->label('نام پکیح')
                    ->relationship('coinPackage', 'name'),
                TextInput::make('reference_code')
                    ->label('کد پیگیری'),
            ]);
    }
}
