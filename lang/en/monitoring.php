<?php

return [
    'navigation' => 'Monitoring',
    'title' => 'Monitoring',

    'stats' => [
        'nodes_online' => 'Nodes Online',
        'nodes_online_desc' => ':offline offline, :errors refresh errors',
        'servers_running' => 'Servers Running',
        'servers_running_desc' => ':offline offline, :errors refresh errors',
        'avg_cpu' => 'Avg. CPU Usage',
        'avg_cpu_desc' => 'Average across all nodes',
        'memory_usage' => 'Memory Usage',
        'disk_usage' => 'Disk Usage',
        'refresh_status' => 'Refresh Status',
        'refresh_status_desc' => 'Last refresh at :time',
    ],

    'charts' => [
        'cpu_all_nodes' => 'CPU Usage',
        'cpu_total_desc' => ':current % total load across :max % capacity',
        'memory_all_nodes' => 'Memory Usage',
        'storage_all_nodes' => 'Storage',
        'server_status' => 'Server Status',
        'server_status_desc' => 'Distribution of server states',
        'used' => 'Used',
        'free' => 'Free',
        'running' => 'Running',
        'stopped' => 'Stopped',
        'starting' => 'Starting',
        'errored' => 'Error',
    ],

    'tables' => [
        'node_health' => 'Node Health Overview',
        'node_health_desc' => 'Status and resource usage of all nodes',
        'top_servers' => 'Server Overview',
        'top_servers_desc' => 'All servers with current resource usage',
        'name' => 'Name',
        'fqdn' => 'FQDN',
        'status' => 'Status',
        'online' => 'Online',
        'offline' => 'Offline',
        'cpu' => 'CPU',
        'memory' => 'Memory',
        'disk' => 'Disk',
        'load' => 'Load (1/5/15)',
        'servers' => 'Servers',
        'node' => 'Node',
        'owner' => 'Owner',
        'server_status' => [
            'running' => 'Running',
            'starting' => 'Starting',
            'offline' => 'Offline',
            'error' => 'Error',
        ],
    ],

    'status' => [
        'heading' => 'Monitoring Refresh',
        'healthy' => 'Healthy',
        'degraded' => 'Degraded',
        'summary' => 'Status: :status. Last refresh: :time.',
        'counts' => ':offline offline nodes, :errors refresh errors.',
        'unknown_time' => 'unknown',
    ],

    'actions' => [
        'refresh' => 'Refresh Now',
        'refresh_success' => 'Monitoring data refresh started.',
    ],
];
