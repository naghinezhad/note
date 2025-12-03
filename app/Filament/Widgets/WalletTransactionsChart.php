<?php

namespace App\Filament\Widgets;

use App\Models\WalletTransaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WalletTransactionsChart extends ChartWidget
{
    protected ?string $heading = 'نمودار مجموع پرداخت‌ها';

    protected function getData(): array
    {
        $months = collect(range(0, 11))
            ->mapWithKeys(fn ($i) => [
                Carbon::now()->subMonths($i)->format('Y-m') => 0,
            ])
            ->reverse();

        $transactions = WalletTransaction::where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->where('paid_amount', '>', 0)
            ->get()
            ->groupBy(fn ($transaction) => Carbon::parse($transaction->created_at)->format('Y-m'))
            ->map(fn ($transactions) => $transactions->sum('paid_amount'));

        $data = $months->merge($transactions);

        return [
            'labels' => $data->keys()
                ->map(fn ($m) => Carbon::parse($m.'-01')->format('M Y'))
                ->toArray(),

            'datasets' => [
                [
                    'label' => 'پرداختی موفق (ریال)',
                    'data' => $data->values()->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
