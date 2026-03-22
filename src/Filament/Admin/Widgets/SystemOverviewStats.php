<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Enums\ContainerStatus;
use App\Models\Node;
use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $nodes = Node::all();
        $totalServers = Server::count();
        $runningServers = 0;

        $totalCpu = 0;
        $totalMemory = 0;
        $totalMemoryUsed = 0;
        $totalDisk = 0;
        $totalDiskUsed = 0;
        $onlineNodes = 0;

        foreach ($nodes as $node) {
            try {
                $stats = $node->statistics();
                $onlineNodes++;

                $cpuCount = $node->systemInformation()['cpu_count'] ?? 1;
                $totalCpu += round($stats['cpu_percent'] * $cpuCount, 2);
                $totalMemory += $stats['memory_total'];
                $totalMemoryUsed += $stats['memory_used'];
                $totalDisk += $stats['disk_total'];
                $totalDiskUsed += $stats['disk_used'];
            } catch (\Exception) {
                //
            }
        }

        foreach (Server::all() as $server) {
            try {
                if ($server->retrieveStatus() === ContainerStatus::Running) {
                    $runningServers++;
                }
            } catch (\Exception) {
                //
            }
        }

        $avgCpu = $onlineNodes > 0 ? round($totalCpu / $onlineNodes, 1) : 0;
        $memoryPercent = $totalMemory > 0 ? round(($totalMemoryUsed / $totalMemory) * 100, 1) : 0;
        $diskPercent = $totalDisk > 0 ? round(($totalDiskUsed / $totalDisk) * 100, 1) : 0;

        return [
            Stat::make(trans('monitoring::monitoring.stats.nodes_online'), "$onlineNodes / {$nodes->count()}")
                ->description(trans('monitoring::monitoring.stats.nodes_online_desc'))
                ->color($onlineNodes === $nodes->count() ? 'success' : 'warning')
                ->chart($this->storeTrend('monitoring.nodes_online', $onlineNodes)),

            Stat::make(trans('monitoring::monitoring.stats.servers_running'), "$runningServers / $totalServers")
                ->description(trans('monitoring::monitoring.stats.servers_running_desc'))
                ->color('primary')
                ->chart($this->storeTrend('monitoring.servers_running', $runningServers)),

            Stat::make(trans('monitoring::monitoring.stats.avg_cpu'), "$avgCpu %")
                ->description(trans('monitoring::monitoring.stats.avg_cpu_desc'))
                ->color($avgCpu > 80 ? 'danger' : ($avgCpu > 60 ? 'warning' : 'success'))
                ->chart($this->storeTrend('monitoring.avg_cpu', $avgCpu)),

            Stat::make(trans('monitoring::monitoring.stats.memory_usage'), "$memoryPercent %")
                ->description(convert_bytes_to_readable($totalMemoryUsed) . ' / ' . convert_bytes_to_readable($totalMemory))
                ->color($memoryPercent > 85 ? 'danger' : ($memoryPercent > 70 ? 'warning' : 'success'))
                ->chart($this->storeTrend('monitoring.memory_percent', $memoryPercent)),

            Stat::make(trans('monitoring::monitoring.stats.disk_usage'), "$diskPercent %")
                ->description(convert_bytes_to_readable($totalDiskUsed) . ' / ' . convert_bytes_to_readable($totalDisk))
                ->color($diskPercent > 90 ? 'danger' : ($diskPercent > 75 ? 'warning' : 'success'))
                ->chart($this->storeTrend('monitoring.disk_percent', $diskPercent)),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }

    private function storeTrend(string $key, float|int $current): array
    {
        $history = session($key, []);
        $history[] = $current;
        $history = array_slice($history, -7);
        session()->put($key, $history);

        return $history;
    }
}
