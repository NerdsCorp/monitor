<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Models\Node;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class NodeHealthTable extends TableWidget
{
    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(Node::query())
            ->heading(trans('monitoring::monitoring.tables.node_health'))
            ->description(trans('monitoring::monitoring.tables.node_health_desc'))
            ->columns([
                TextColumn::make('name')
                    ->label(trans('monitoring::monitoring.tables.name'))
                    ->sortable(),

                TextColumn::make('fqdn')
                    ->label(trans('monitoring::monitoring.tables.fqdn')),

                TextColumn::make('status')
                    ->label(trans('monitoring::monitoring.tables.status'))
                    ->badge()
                    ->getStateUsing(function (Node $record) {
                        try {
                            $record->statistics();

                            return trans('monitoring::monitoring.tables.online');
                        } catch (\Exception) {
                            return trans('monitoring::monitoring.tables.offline');
                        }
                    })
                    ->color(fn (string $state) => $state === trans('monitoring::monitoring.tables.online') ? 'success' : 'danger'),

                TextColumn::make('cpu_usage')
                    ->label(trans('monitoring::monitoring.tables.cpu'))
                    ->getStateUsing(function (Node $record) {
                        try {
                            $stats = $record->statistics();
                            $cpuCount = $record->systemInformation()['cpu_count'] ?? 1;

                            return format_number(round($stats['cpu_percent'] * $cpuCount, 1), maxPrecision: 1) . ' %';
                        } catch (\Exception) {
                            return '-';
                        }
                    }),

                TextColumn::make('memory_usage')
                    ->label(trans('monitoring::monitoring.tables.memory'))
                    ->getStateUsing(function (Node $record) {
                        try {
                            $stats = $record->statistics();

                            return convert_bytes_to_readable($stats['memory_used']) . ' / ' . convert_bytes_to_readable($stats['memory_total']);
                        } catch (\Exception) {
                            return '-';
                        }
                    }),

                TextColumn::make('disk_usage')
                    ->label(trans('monitoring::monitoring.tables.disk'))
                    ->getStateUsing(function (Node $record) {
                        try {
                            $stats = $record->statistics();

                            return convert_bytes_to_readable($stats['disk_used']) . ' / ' . convert_bytes_to_readable($stats['disk_total']);
                        } catch (\Exception) {
                            return '-';
                        }
                    }),

                TextColumn::make('load_average')
                    ->label(trans('monitoring::monitoring.tables.load'))
                    ->getStateUsing(function (Node $record) {
                        try {
                            $stats = $record->statistics();

                            return format_number($stats['load_average1'], maxPrecision: 2) . ' / '
                                . format_number($stats['load_average5'], maxPrecision: 2) . ' / '
                                . format_number($stats['load_average15'], maxPrecision: 2);
                        } catch (\Exception) {
                            return '-';
                        }
                    }),

                TextColumn::make('servers_count')
                    ->label(trans('monitoring::monitoring.tables.servers'))
                    ->counts('servers')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->paginated(false);
    }
}
