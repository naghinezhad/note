<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                ImageColumn::make('image_profile')
                    ->label('عکس پروفایل')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable(),
                TextColumn::make('wallet.coins')
                    ->label('کیف پول'),
                TextColumn::make('is_admin')
                    ->label('نقش')
                    ->formatStateUsing(fn ($state) => $state ? 'ادمین' : 'کاربر عادی')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('email_verified_at')
                    ->label('تاریخ تأیید ایمیل')
                    ->dateTime()
                    ->jalaliDateTime()
                    ->sortable()
                    ->placeholder('تأیید نشده'),
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
                DeleteAction::make()
                    ->using(function ($record, $action) {
                        if (DB::table('sessions')->where('user_id', $record->id)->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('خطا در حذف')
                                ->body('این کاربر در حال حاضر سشن فعال دارد و قابل حذف نیست.')
                                ->send();

                            $action->cancel();

                            return;
                        }

                        $record->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function ($records, $action) {
                            $cannotDelete = [];

                            foreach ($records as $record) {
                                if (DB::table('sessions')->where('user_id', $record->id)->exists()) {
                                    $cannotDelete[] = $record->name;

                                    continue;
                                }

                                $record->delete();
                            }

                            if (! empty($cannotDelete)) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('خطا در حذف')
                                    ->body('کاربر(های) '.implode(', ', $cannotDelete).' در حال حاضر سشن فعال دارند و قابل حذف نیستند.')
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
