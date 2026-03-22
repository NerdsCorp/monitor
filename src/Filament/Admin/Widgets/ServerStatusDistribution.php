<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Enums\ContainerStatus;
use App\Models\Server;
use Filament\Widgets\ChartWidget;

class ServerStatusDistribution extends ChartWidget
{
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
        $running = 0;
        $stopped = 0;
        $starting = 0;
        $errored = 0;

        foreach (Server::all() as $server) {
            try {
                $status = $server->retrieveStatus();

                if ($status === ContainerStatus::Running) {
                    $running++;
                } elseif ($status === ContainerStatus::Starting) {
                    $starting++;
                } elseif ($status->isOffline()) {
                    $stopped++;
                } else {
                    $errored++;
                }
            } catch (\Exception) {
                $errored++;
            }
        }

        return [
            'datasets' => [
                [
                    'data' => [$running, $stopped, $starting, $errored],
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
