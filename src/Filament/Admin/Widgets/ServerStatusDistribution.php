<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use Pelican\Monitoring\Concerns\InteractsWithMonitoringData;
use Filament\Widgets\ChartWidget;

class ServerStatusDistribution extends ChartWidget
{
    use InteractsWithMonitoringData;

    protected ?string $pollingInterval = '30s';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'lg' => 1,
    ];

    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        $distribution = $this->getMonitoringSnapshot()['server_status_distribution'] ?? [];

        return [
            'datasets' => [
                [
                    'data' => [
                        $distribution['running'] ?? 0,
                        $distribution['stopped'] ?? 0,
                        $distribution['starting'] ?? 0,
                        $distribution['errored'] ?? 0,
                    ],
                    'backgroundColor' => [
                        'rgba(74, 222, 128, 0.8)',
                        'rgba(156, 163, 175, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    'borderColor' => [
                        'rgba(74, 222, 128, 1)',
                        'rgba(156, 163, 175, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => [
                trans('monitoring::monitoring.charts.running'),
                trans('monitoring::monitoring.charts.stopped'),
                trans('monitoring::monitoring.charts.starting'),
                trans('monitoring::monitoring.charts.errored'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected ?array $options = [
        'scales' => [
            'x' => ['display' => false],
            'y' => ['display' => false],
        ],
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'bottom',
                'labels' => [
                    'usePointStyle' => true,
                    'pointStyle' => 'circle',
                    'boxWidth' => 6,
                ],
            ],
        ],
    ];

    public function getHeading(): string
    {
        return trans('monitoring::monitoring.charts.server_status');
    }

    public function getDescription(): ?string
    {
        return trans('monitoring::monitoring.charts.server_status_desc');
    }
}
