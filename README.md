# Monitoring Plugin

Monitoring is a Pelican admin-panel plugin that gives operators a single monitoring page for node health, server state, CPU, memory, and storage usage.

## Features

- Shared `MonitoringDataService` snapshot cache with a 10-second TTL to avoid repeated node and server polling inside every widget.
- Dashboard stat cards for online nodes, running servers, average CPU, memory, disk, and refresh health.
- Visible degraded-state banner with last refresh time and refresh error summaries.
- CPU, memory, storage, and server-status charts.
- Node health and server overview tables.
- Node and owner filters on the server overview table.
- Default server ordering by highest CPU usage.

## Installation

1. Place the plugin in your Pelican plugins directory as `monitoring`.
2. Ensure the plugin manifest files `plugin.json` and `update.json` remain in the plugin root.
3. Enable the plugin for the admin panel in Pelican.
4. Reload the admin panel and open the `Monitoring` page from the admin navigation.
5. Watch your Laravel logs after first install to confirm nodes and servers are refreshing cleanly.

## Version Support

- Panel scope: `admin`
- Pelican compatibility: this plugin targets the current Pelican admin panel plugin API used by this repository.
- Manifest note: `panel_version` is still unset in `plugin.json`, so you should validate against your Pelican build in staging before marketplace release.


## Operational Notes

- Refresh data is cached for 10 seconds, which reduces repeated remote calls but means values are intentionally not real-time per request.
- Trend history is cached and shared so charts survive page reloads for a short rolling window.
- Node or server refresh failures are logged with warning-level entries and surfaced in the dashboard banner.
- Offline nodes and refresh errors are shown directly in the stat cards.

## Known Limits

- The plugin still depends on Pelican's live node and server APIs, so large fleets can take time to refresh when the cache expires.
- Server ordering is computed from the cached snapshot, not from a database column.
- The screenshots in this repository are lightweight preview assets, not live captures from your own panel.
