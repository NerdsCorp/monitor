<?php

namespace Pelican\Monitoring\Concerns;

use Pelican\Monitoring\Services\MonitoringDataService;

trait InteractsWithMonitoringData
{
    protected ?array $monitoringSnapshot = null;

    protected function getMonitoringSnapshot(): array
    {
        return $this->monitoringSnapshot ??= app(MonitoringDataService::class)->getSnapshot();
    }

    protected function getNodeMetric(int $nodeId): array
    {
        return $this->getMonitoringSnapshot()['nodes'][$nodeId] ?? [];
    }

    protected function getServerMetric(int $serverId): array
    {
        return $this->getMonitoringSnapshot()['servers'][$serverId] ?? [];
    }
}
