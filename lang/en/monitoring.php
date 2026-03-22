<?php

return [
    'navigation' => 'Monitoring',
    'title' => 'Monitoring',

    'stats' => [
        'nodes_online' => 'Nodes Online',
        'nodes_online_desc' => 'Connected nodes',
        'servers_running' => 'Servers Running',
        'servers_running_desc' => 'Active servers',
        'avg_cpu' => 'Avg. CPU Usage',
        'avg_cpu_desc' => 'Average across all nodes',
        'memory_usage' => 'Memory Usage',
        'disk_usage' => 'Disk Usage',
    ],

    'charts' => [
        'cpu_all_nodes' => 'CPU Usage',
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
    ],
];
