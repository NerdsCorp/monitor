<?php

return [
    'navigation' => 'Monitoring',
    'title' => 'Monitoring',

    'stats' => [
        'nodes_online' => 'Nodes Online',
        'nodes_online_desc' => 'Verbundene Nodes',
        'servers_running' => 'Server Aktiv',
        'servers_running_desc' => 'Laufende Server',
        'avg_cpu' => 'Ø CPU-Auslastung',
        'avg_cpu_desc' => 'Durchschnitt aller Nodes',
        'memory_usage' => 'Arbeitsspeicher',
        'disk_usage' => 'Festplatte',
    ],

    'charts' => [
        'cpu_all_nodes' => 'CPU-Auslastung',
        'memory_all_nodes' => 'Arbeitsspeicher',
        'storage_all_nodes' => 'Speicherplatz',
        'server_status' => 'Server-Status',
        'server_status_desc' => 'Verteilung der Server-Zustände',
        'used' => 'Belegt',
        'free' => 'Frei',
        'running' => 'Läuft',
        'stopped' => 'Gestoppt',
        'starting' => 'Startet',
        'errored' => 'Fehler',
    ],

    'tables' => [
        'node_health' => 'Node-Gesundheit',
        'node_health_desc' => 'Status und Ressourcenauslastung aller Nodes',
        'top_servers' => 'Server-Übersicht',
        'top_servers_desc' => 'Alle Server mit aktuellem Ressourcenverbrauch',
        'name' => 'Name',
        'fqdn' => 'FQDN',
        'status' => 'Status',
        'online' => 'Online',
        'offline' => 'Offline',
        'cpu' => 'CPU',
        'memory' => 'Speicher',
        'disk' => 'Festplatte',
        'load' => 'Last (1/5/15)',
        'servers' => 'Server',
        'node' => 'Node',
        'owner' => 'Besitzer',
    ],
];
