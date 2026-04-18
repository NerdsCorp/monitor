<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use Pelican\Monitoring\Concerns\InteractsWithMonitoringData;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class AllNodesMemoryChart extends ChartWidget
{
    use InteractsWithMonitoringData;

    protected ?string $pollingInterval = '10s';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'lg' => 1,
    ];

    protected static bool $isDiscovered = false;

    protected int $currentMemoryTotal = 0;

    protected int $maxMemoryTotal = 0;

    protected function getData(): array
    {
        $snapshot = $this->getMonitoringSnapshot();
        $nodes = array_values($snapshot['nodes'] ?? []);
        $datasets = [];
        $labels = [];
        $summary = $snapshot['summary'] ?? [];
        $this->currentMemoryTotal = (int) ($summary['memory_used'] ?? 0);
        $this->maxMemoryTotal = (int) ($summary['memory_total'] ?? 0);
        $colors = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(6, 182, 212, 0.8)',
            'rgba(132, 204, 22, 0.8)',
        ];

        foreach ($nodes as $index => $node) {
            $color = $colors[$index % count($colors)];
            $history = $node['memory_history'] ?? [];

            $datasets[] = [
                'label' => $node['name'],
                'data' => array_column($history, 'value'),
                'borderColor' => $color,
                'backgroundColor' => str_replace('0.8', '0.1', $color),
                'tension' => 0.3,
                'fill' => true,
                'pointRadius' => 0,
                'borderWidth' => 2,
            ];

            if (empty($labels) || count(array_column($history, 'timestamp')) > count($labels)) {
                $labels = array_column($history, 'timestamp');
            }
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + ' %';
                        }
                    },
                    grid: {
                        color: 'rgba(128, 128, 128, 0.1)',
                    },
                },
                x: {
                    display: false,
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
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' %';
                        }
                    }
                },
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false,
            },
        }
        JS);
    }

    public function getHeading(): string
    {
        return trans('monitoring::monitoring.charts.memory_all_nodes');
    }

    public function getDescription(): ?string
    {
        $used = convert_bytes_to_readable($this->currentMemoryTotal);
        $total = convert_bytes_to_readable($this->maxMemoryTotal);

        return "{$used} / {$total}";
    }
}
