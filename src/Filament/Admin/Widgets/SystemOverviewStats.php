<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use Pelican\Monitoring\Concerns\InteractsWithMonitoringData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewStats extends StatsOverviewWidget
{
    use InteractsWithMonitoringData;

    protected ?string $pollingInterval = '10s';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $snapshot = $this->getMonitoringSnapshot();
        $summary = $snapshot['summary'] ?? [];
        $trends = $snapshot['trends'] ?? [];
        $status = $snapshot['status'] ?? 'healthy';
        $timezone = auth()->user()?->timezone ?? config('app.timezone');
        $refreshedAt = isset($snapshot['refreshed_at'])
            ? \Illuminate\Support\Carbon::parse($snapshot['refreshed_at'])->timezone($timezone)->format('H:i:s')
            : trans('monitoring::monitoring.status.unknown_time');

        return [
            Stat::make(
                trans('monitoring::monitoring.stats.nodes_online'),
                ($summary['nodes_online'] ?? 0) . ' / ' . ($summary['nodes_total'] ?? 0),
            )
                ->description(trans('monitoring::monitoring.stats.nodes_online_desc', [
                    'offline' => $summary['nodes_offline'] ?? 0,
                    'errors' => $summary['node_errors'] ?? 0,
                ]))
                ->color(($summary['nodes_offline'] ?? 0) > 0 ? 'warning' : 'success')
                ->chart(array_column($trends['nodes_online'] ?? [], 'value')),

            Stat::make(
                trans('monitoring::monitoring.stats.servers_running'),
                ($summary['servers_running'] ?? 0) . ' / ' . ($summary['servers_total'] ?? 0),
            )
                ->description(trans('monitoring::monitoring.stats.servers_running_desc', [
                    'offline' => $summary['servers_offline'] ?? 0,
                    'errors' => $summary['server_errors'] ?? 0,
                ]))
                ->color(($summary['server_errors'] ?? 0) > 0 ? 'warning' : 'primary')
                ->chart(array_column($trends['servers_running'] ?? [], 'value')),

            Stat::make(trans('monitoring::monitoring.stats.avg_cpu'), ($summary['avg_cpu_percent'] ?? 0) . ' %')
                ->description(trans('monitoring::monitoring.stats.avg_cpu_desc'))
                ->color(($summary['avg_cpu_percent'] ?? 0) > 80 ? 'danger' : (($summary['avg_cpu_percent'] ?? 0) > 60 ? 'warning' : 'success'))
                ->chart(array_column($trends['avg_cpu_percent'] ?? [], 'value')),

            Stat::make(trans('monitoring::monitoring.stats.memory_usage'), ($summary['memory_percent'] ?? 0) . ' %')
                ->description(convert_bytes_to_readable($summary['memory_used'] ?? 0) . ' / ' . convert_bytes_to_readable($summary['memory_total'] ?? 0))
                ->color(($summary['memory_percent'] ?? 0) > 85 ? 'danger' : (($summary['memory_percent'] ?? 0) > 70 ? 'warning' : 'success'))
                ->chart(array_column($trends['memory_percent'] ?? [], 'value')),

            Stat::make(trans('monitoring::monitoring.stats.disk_usage'), ($summary['disk_percent'] ?? 0) . ' %')
                ->description(convert_bytes_to_readable($summary['disk_used'] ?? 0) . ' / ' . convert_bytes_to_readable($summary['disk_total'] ?? 0))
                ->color(($summary['disk_percent'] ?? 0) > 90 ? 'danger' : (($summary['disk_percent'] ?? 0) > 75 ? 'warning' : 'success'))
                ->chart(array_column($trends['disk_percent'] ?? [], 'value')),

            Stat::make(
                trans('monitoring::monitoring.stats.refresh_status'),
                trans("monitoring::monitoring.status.{$status}"),
            )
                ->description(trans('monitoring::monitoring.stats.refresh_status_desc', ['time' => $refreshedAt]))
                ->color($status === 'healthy' ? 'success' : 'warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 6;
    }
}
