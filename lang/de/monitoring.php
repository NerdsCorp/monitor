<?php

return [
    'navigation' => 'Monitoring',
    'title' => 'Monitoring',

    'stats' => [
        'nodes_online' => 'Nodes Online',
        'nodes_online_desc' => ':offline offline, :errors Aktualisierungsfehler',
        'servers_running' => 'Server Aktiv',
        'servers_running_desc' => ':offline offline, :errors Aktualisierungsfehler',
        'avg_cpu' => 'Ø CPU-Auslastung',
        'avg_cpu_desc' => 'Durchschnitt aller Nodes',
        'memory_usage' => 'Arbeitsspeicher',
        'disk_usage' => 'Festplatte',
        'refresh_status' => 'Aktualisierungsstatus',
        'refresh_status_desc' => 'Letzte Aktualisierung um :time',
    ],

    'charts' => [
        'cpu_all_nodes' => 'CPU-Auslastung',
        'cpu_total_desc' => ':current % Gesamtlast bei :max % Kapazität',
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
        'server_status' => [
            'running' => 'Läuft',
            'starting' => 'Startet',
            'offline' => 'Offline',
            'error' => 'Fehler',
        ],
    ],

    'status' => [
        'heading' => 'Monitoring-Aktualisierung',
        'healthy' => 'Stabil',
        'degraded' => 'Eingeschränkt',
        'summary' => 'Status: :status. Letzte Aktualisierung: :time.',
        'counts' => ':offline offline Nodes, :errors Aktualisierungsfehler.',
        'unknown_time' => 'unbekannt',
    ],
];
