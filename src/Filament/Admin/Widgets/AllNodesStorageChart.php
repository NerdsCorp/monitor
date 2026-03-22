<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Models\Node;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class AllNodesStorageChart extends ChartWidget
{
    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'lg' => 1,
    ];

    protected static bool $isDiscovered = false;

    protected int $currentDiskTotal = 0;

    protected int $maxDiskTotal = 0;

    protected function getData(): array
    {
        $nodes = Node::all();
        $labels = [];
        $usedData = [];
        $freeData = [];
        $this->currentDiskTotal = 0;
        $this->maxDiskTotal = 0;

        foreach ($nodes as $node) {
            $labels[] = $node->name;

            try {
                $stats = $node->statistics();
                $this->currentDiskTotal += $stats['disk_used'];
                $this->maxDiskTotal += $stats['disk_total'];
                $usedGb = round($stats['disk_used'] / (config('panel.use_binary_prefix') ? 1073741824 : 1000000000), 1);
                $freeGb = round(($stats['disk_total'] - $stats['disk_used']) / (config('panel.use_binary_prefix') ? 1073741824 : 1000000000), 1);
            } catch (\Exception) {
                $usedGb = 0;
                $freeGb = 0;
            }

            $usedData[] = $usedGb;
            $freeData[] = $freeGb;
        }

        $suffix = config('panel.use_binary_prefix') ? 'GiB' : 'GB';

        return [
            'datasets' => [
                [
                    'label' => trans('monitoring::monitoring.charts.used'),
                    'data' => $usedData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
                [
                    'label' => trans('monitoring::monitoring.charts.free'),
                    'data' => $freeData,
                    'backgroundColor' => 'rgba(74, 222, 128, 0.7)',
                    'borderColor' => 'rgba(74, 222, 128, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        $suffix = config('panel.use_binary_prefix') ? 'GiB' : 'GB';

        return RawJs::make(<<<JS
        {
            scales: {
                y: {
                    stacked: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' $suffix';
                        }
                    },
                    grid: {
                        color: 'rgba(128, 128, 128, 0.1)',
                    },
                },
                x: {
                    stacked: true,
                    grid: {
                        display: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 20,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' $suffix';
                        }
                    }
                },
            },
        }
        JS);
    }

    public function getHeading(): string
    {
        return trans('monitoring::monitoring.charts.storage_all_nodes');
    }

    public function getDescription(): ?string
    {
        $used = convert_bytes_to_readable($this->currentDiskTotal);
        $total = convert_bytes_to_readable($this->maxDiskTotal);

        return "{$used} / {$total}";
    }
}
