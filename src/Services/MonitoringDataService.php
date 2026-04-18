<?php

namespace Pelican\Monitoring\Services;

use App\Enums\ContainerStatus;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MonitoringDataService
{
    private const SNAPSHOT_CACHE_KEY = 'monitoring.snapshot.v2';

    private const SNAPSHOT_TTL_SECONDS = 10;

    private const HISTORY_LIMIT = 30;

    private const HISTORY_TTL_SECONDS = 3600;

    public function getSnapshot(): array
    {
        return Cache::remember(
            self::SNAPSHOT_CACHE_KEY,
            now()->addSeconds(self::SNAPSHOT_TTL_SECONDS),
            fn (): array => $this->buildSnapshot(),
        );
    }

    public function forgetSnapshot(): void
    {
        Cache::forget(self::SNAPSHOT_CACHE_KEY);
    }

    public function getSortedServerIds(string $metric = 'cpu_percent', string $direction = 'desc'): array
    {
        return $this->getSortedMetricIds(
            array_values($this->getSnapshot()['servers'] ?? []),
            $metric,
            $direction,
        );
    }

    public function getSortedNodeIds(string $metric = 'name', string $direction = 'asc'): array
    {
        return $this->getSortedMetricIds(
            array_values($this->getSnapshot()['nodes'] ?? []),
            $metric,
            $direction,
        );
    }

    private function buildSnapshot(): array
    {
        $refreshedAt = now();
        $nodes = Node::query()->withCount('servers')->get();
        $servers = Server::query()->with(['node', 'user:id,username'])->get();

        $nodeMetrics = [];
        $serverMetrics = [];
        $messages = [];
        $serverNodeErrors = [];

        $onlineNodes = 0;
        $nodeErrors = 0;
        $runningServers = 0;
        $serverOffline = 0;
        $serverErrors = 0;

        $avgCpuAccumulator = 0.0;
        $currentCpuTotal = 0.0;
        $cpuCapacityTotal = 0.0;
        $memoryTotal = 0;
        $memoryUsed = 0;
        $diskTotal = 0;
        $diskUsed = 0;

        $serverStatusDistribution = [
            'running' => 0,
            'stopped' => 0,
            'starting' => 0,
            'errored' => 0,
        ];

        foreach ($nodes as $node) {
            $metric = [
                'id' => $node->id,
                'name' => $node->name,
                'fqdn' => $node->fqdn,
                'servers_count' => (int) ($node->servers_count ?? 0),
                'status' => 'offline',
                'status_label' => 'offline',
                'status_rank' => 1,
                'cpu_count' => 0,
                'cpu_usage_percent' => 0.0,
                'cpu_total_percent' => 0.0,
                'memory_total' => 0,
                'memory_used' => 0,
                'memory_percent' => 0.0,
                'disk_total' => 0,
                'disk_used' => 0,
                'disk_percent' => 0.0,
                'load_average_1' => 0.0,
                'load_average_5' => 0.0,
                'load_average_15' => 0.0,
                'error' => null,
                'cpu_history' => [],
                'memory_history' => [],
            ];

            try {
                $stats = $node->statistics();
                $systemInformation = $node->systemInformation();
                $cpuCount = max(1, (int) ($systemInformation['cpu_count'] ?? 1));
                $cpuUsagePercent = round((float) ($stats['cpu_percent'] ?? 0), 1);
                $cpuTotalPercent = round($cpuUsagePercent * $cpuCount, 1);
                $nodeMemoryTotal = (int) ($stats['memory_total'] ?? 0);
                $nodeMemoryUsed = (int) ($stats['memory_used'] ?? 0);
                $nodeDiskTotal = (int) ($stats['disk_total'] ?? 0);
                $nodeDiskUsed = (int) ($stats['disk_used'] ?? 0);

                $metric = array_merge($metric, [
                    'status' => 'online',
                    'status_label' => 'online',
                    'status_rank' => 0,
                    'cpu_count' => $cpuCount,
                    'cpu_usage_percent' => $cpuUsagePercent,
                    'cpu_total_percent' => $cpuTotalPercent,
                    'memory_total' => $nodeMemoryTotal,
                    'memory_used' => $nodeMemoryUsed,
                    'memory_percent' => $nodeMemoryTotal > 0 ? round(($nodeMemoryUsed / $nodeMemoryTotal) * 100, 1) : 0.0,
                    'disk_total' => $nodeDiskTotal,
                    'disk_used' => $nodeDiskUsed,
                    'disk_percent' => $nodeDiskTotal > 0 ? round(($nodeDiskUsed / $nodeDiskTotal) * 100, 1) : 0.0,
                    'load_average_1' => round((float) ($stats['load_average1'] ?? 0), 2),
                    'load_average_5' => round((float) ($stats['load_average5'] ?? 0), 2),
                    'load_average_15' => round((float) ($stats['load_average15'] ?? 0), 2),
                ]);

                $onlineNodes++;
                $avgCpuAccumulator += $cpuUsagePercent;
                $currentCpuTotal += $cpuTotalPercent;
                $cpuCapacityTotal += $cpuCount * 100;
                $memoryTotal += $nodeMemoryTotal;
                $memoryUsed += $nodeMemoryUsed;
                $diskTotal += $nodeDiskTotal;
                $diskUsed += $nodeDiskUsed;
            } catch (Throwable $exception) {
                $nodeErrors++;
                $metric['error'] = $exception->getMessage();
                $messages["node-{$node->id}"] = "Node {$node->name} could not be refreshed.";

                Log::warning('Monitoring plugin failed to refresh node statistics.', [
                    'node_id' => $node->id,
                    'node_name' => $node->name,
                    'exception' => $exception,
                ]);
            }

            $metric['cpu_history'] = $this->pushHistory(
                "monitoring.history.node_cpu.{$node->id}",
                $metric['cpu_usage_percent'],
                $refreshedAt,
            );
            $metric['memory_history'] = $this->pushHistory(
                "monitoring.history.node_memory.{$node->id}",
                $metric['memory_percent'],
                $refreshedAt,
            );

            $nodeMetrics[$node->id] = $metric;
        }

        foreach ($servers as $server) {
            $metric = [
                'id' => $server->id,
                'name' => $server->name,
                'node_id' => $server->node_id,
                'node_name' => $server->node?->name,
                'user_id' => $server->user_id,
                'owner' => $server->user?->username,
                'status' => 'error',
                'status_rank' => 3,
                'cpu_percent' => 0.0,
                'memory_bytes' => 0,
                'disk_bytes' => 0,
                'error' => null,
            ];

            try {
                $cachedNodeError = $serverNodeErrors[$server->node_id] ?? null;

                if ($cachedNodeError !== null) {
                    throw new \RuntimeException($cachedNodeError);
                }

                $nodeEndpointError = $this->getNodeEndpointConfigurationError($server->node);

                if ($nodeEndpointError !== null) {
                    $serverNodeErrors[$server->node_id] = $nodeEndpointError;
                    throw new \RuntimeException($nodeEndpointError);
                }

                $status = $server->retrieveStatus();
                $resources = $server->retrieveResources();
                $statusKey = $this->normalizeServerStatus($status);

                $metric = array_merge($metric, [
                    'status' => $statusKey,
                    'status_rank' => $this->getServerStatusRank($statusKey),
                    'cpu_percent' => round((float) ($resources['cpu_absolute'] ?? 0), 1),
                    'memory_bytes' => (int) ($resources['memory_bytes'] ?? 0),
                    'disk_bytes' => (int) ($resources['disk_bytes'] ?? 0),
                ]);

                if ($status === ContainerStatus::Running) {
                    $runningServers++;
                }

                if ($statusKey === 'offline') {
                    $serverOffline++;
                }

                if ($statusKey === 'running') {
                    $serverStatusDistribution['running']++;
                } elseif ($statusKey === 'starting') {
                    $serverStatusDistribution['starting']++;
                } elseif ($statusKey === 'offline') {
                    $serverStatusDistribution['stopped']++;
                } else {
                    $serverStatusDistribution['errored']++;
                }
            } catch (Throwable $exception) {
                $serverErrors++;
                $serverStatusDistribution['errored']++;
                $metric['error'] = $this->normalizeRefreshErrorMessage($exception);
                $serverNodeErrors[$server->node_id] ??= $metric['error'];
                $messages["server-{$server->id}"] = "Server {$server->name} could not be refreshed: {$metric['error']}";

                Log::warning('Monitoring plugin failed to refresh server metrics.', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'node_id' => $server->node_id,
                    'node_name' => $server->node?->name,
                    'exception' => $exception,
                ]);
            }

            $serverMetrics[$server->id] = $metric;
        }

        $summary = [
            'nodes_total' => $nodes->count(),
            'nodes_online' => $onlineNodes,
            'nodes_offline' => $nodes->count() - $onlineNodes,
            'node_errors' => $nodeErrors,
            'servers_total' => $servers->count(),
            'servers_running' => $runningServers,
            'servers_offline' => $serverOffline,
            'server_errors' => $serverErrors,
            'avg_cpu_percent' => $onlineNodes > 0 ? round($avgCpuAccumulator / $onlineNodes, 1) : 0.0,
            'current_cpu_total' => round($currentCpuTotal, 1),
            'cpu_capacity_total' => round($cpuCapacityTotal, 0),
            'memory_total' => $memoryTotal,
            'memory_used' => $memoryUsed,
            'memory_percent' => $memoryTotal > 0 ? round(($memoryUsed / $memoryTotal) * 100, 1) : 0.0,
            'disk_total' => $diskTotal,
            'disk_used' => $diskUsed,
            'disk_percent' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0.0,
        ];

        return [
            'refreshed_at' => $refreshedAt->toIso8601String(),
            'cache_ttl_seconds' => self::SNAPSHOT_TTL_SECONDS,
            'status' => empty($messages) ? 'healthy' : 'degraded',
            'messages' => array_values($messages),
            'summary' => $summary,
            'trends' => [
                'nodes_online' => $this->pushHistory('monitoring.history.summary.nodes_online', $summary['nodes_online'], $refreshedAt),
                'servers_running' => $this->pushHistory('monitoring.history.summary.servers_running', $summary['servers_running'], $refreshedAt),
                'avg_cpu_percent' => $this->pushHistory('monitoring.history.summary.avg_cpu_percent', $summary['avg_cpu_percent'], $refreshedAt),
                'memory_percent' => $this->pushHistory('monitoring.history.summary.memory_percent', $summary['memory_percent'], $refreshedAt),
                'disk_percent' => $this->pushHistory('monitoring.history.summary.disk_percent', $summary['disk_percent'], $refreshedAt),
            ],
            'nodes' => $nodeMetrics,
            'servers' => $serverMetrics,
            'server_status_distribution' => $serverStatusDistribution,
        ];
    }

    private function normalizeServerStatus(ContainerStatus $status): string
    {
        if ($status === ContainerStatus::Running) {
            return 'running';
        }

        if ($status === ContainerStatus::Starting) {
            return 'starting';
        }

        return $status->isOffline() ? 'offline' : 'error';
    }

    private function getServerStatusRank(string $status): int
    {
        return match ($status) {
            'running' => 0,
            'starting' => 1,
            'offline' => 2,
            default => 3,
        };
    }

    private function getNodeEndpointConfigurationError(?Node $node): ?string
    {
        if ($node === null) {
            return 'Node relationship is missing.';
        }

        $scheme = $this->extractNodeScheme($node);
        $host = $this->extractNodeHost($node);
        $port = $this->extractNodePort($node);

        if ($scheme === null) {
            return "Node {$node->name} is missing a daemon scheme.";
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            return "Node {$node->name} has an invalid daemon scheme: {$scheme}.";
        }

        if ($host === null) {
            return "Node {$node->name} is missing a daemon host or FQDN.";
        }

        if ($port === null) {
            return "Node {$node->name} is missing a valid daemon port.";
        }

        return null;
    }

    private function extractNodeScheme(Node $node): ?string
    {
        $scheme = $this->firstFilledNodeValue($node, [
            'scheme',
            'daemon_scheme',
        ]);

        if (is_string($scheme) && $scheme !== '') {
            return Str::lower($scheme);
        }

        $url = $this->firstFilledNodeValue($node, [
            'daemon_base',
            'daemon_url',
            'base_url',
        ]);

        if (is_string($url) && str_contains($url, '://')) {
            return Str::lower((string) parse_url($url, PHP_URL_SCHEME));
        }

        return null;
    }

    private function extractNodeHost(Node $node): ?string
    {
        $host = $this->firstFilledNodeValue($node, [
            'fqdn',
            'daemon_host',
            'host',
        ]);

        if (is_string($host) && trim($host) !== '') {
            return trim($host);
        }

        $url = $this->firstFilledNodeValue($node, [
            'daemon_base',
            'daemon_url',
            'base_url',
        ]);

        if (is_string($url) && str_contains($url, '://')) {
            $parsedHost = parse_url($url, PHP_URL_HOST);

            return is_string($parsedHost) && $parsedHost !== '' ? $parsedHost : null;
        }

        return null;
    }

    private function extractNodePort(Node $node): ?int
    {
        $port = $this->firstFilledNodeValue($node, [
            'daemon_listen',
            'daemon_port',
            'port',
        ]);

        if (is_numeric($port)) {
            $port = (int) $port;

            return ($port >= 1 && $port <= 65535) ? $port : null;
        }

        $url = $this->firstFilledNodeValue($node, [
            'daemon_base',
            'daemon_url',
            'base_url',
        ]);

        if (is_string($url) && str_contains($url, '://')) {
            $parsedPort = parse_url($url, PHP_URL_PORT);

            if (is_int($parsedPort) && $parsedPort >= 1 && $parsedPort <= 65535) {
                return $parsedPort;
            }
        }

        return null;
    }

    private function firstFilledNodeValue(Node $node, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($node, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeRefreshErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'cURL error 3')) {
            return 'The assigned node has an invalid daemon URL configuration.';
        }

        if (str_contains($message, 'cURL error 28') && str_contains($message, 'Resolving timed out')) {
            return 'The assigned node hostname could not be resolved within the timeout window.';
        }

        if (str_contains($message, 'cURL error 28') && str_contains($message, 'Operation timed out')) {
            return 'The assigned node accepted the hostname lookup but did not return a response before the timeout.';
        }

        if (str_contains($message, 'cURL error 28')) {
            return 'The assigned node did not respond before the request timed out.';
        }

        return $message;
    }

    private function getSortedMetricIds(array $items, string $metric, string $direction): array
    {
        $direction = Str::lower($direction) === 'asc' ? 'asc' : 'desc';

        usort($items, function (array $left, array $right) use ($metric, $direction): int {
            $leftValue = $left[$metric] ?? null;
            $rightValue = $right[$metric] ?? null;
            $comparison = $this->compareMetricValues($leftValue, $rightValue);

            if ($comparison === 0) {
                $comparison = strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            }

            return $direction === 'asc' ? $comparison : -$comparison;
        });

        return array_column($items, 'id');
    }

    private function compareMetricValues(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return $left <=> $right;
        }

        return strcmp((string) $left, (string) $right);
    }

    private function pushHistory(string $key, float|int $value, \DateTimeInterface $timestamp): array
    {
        $history = Cache::get($key, []);
        $formattedTimestamp = $timestamp->format('H:i:s');

        if ($history !== []) {
            $lastIndex = array_key_last($history);

            if (($history[$lastIndex]['timestamp'] ?? null) === $formattedTimestamp) {
                $history[$lastIndex]['value'] = $value;
            } else {
                $history[] = [
                    'timestamp' => $formattedTimestamp,
                    'value' => $value,
                ];
            }
        } else {
            $history[] = [
                'timestamp' => $formattedTimestamp,
                'value' => $value,
            ];
        }

        $history = array_slice($history, -self::HISTORY_LIMIT);

        Cache::put($key, $history, now()->addSeconds(self::HISTORY_TTL_SECONDS));

        return $history;
    }
}
