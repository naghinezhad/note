<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class UsersChart extends ChartWidget
{
    protected ?string $heading = 'نمودار رشد کاربران';

    protected function getData(): array
    {
        $months = collect(range(0, 11))
            ->mapWithKeys(fn ($i) => [
                Carbon::now()->subMonths($i)->format('Y-m') => 0,
            ]);

        $users = User::where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->get()
            ->groupBy(fn ($user) => Carbon::parse($user->created_at)->format('Y-m'))
            ->map(fn ($users) => $users->count());

        $data = $months->merge($users);

        return [
            'labels' => $data->keys()
                ->map(fn ($m) => Carbon::parse($m.'-01')->format('M Y'))
                ->toArray(),

            'datasets' => [
                [
                    'label' => 'کاربران جدید',
                    'data' => $data->values()->toArray(),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
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
