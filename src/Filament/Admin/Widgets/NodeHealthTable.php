<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Models\Node;
use Pelican\Monitoring\Concerns\InteractsWithMonitoringData;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class NodeHealthTable extends TableWidget
{
    use InteractsWithMonitoringData;

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
                    ->getStateUsing(fn (Node $record): string => trans('monitoring::monitoring.tables.' . ($this->getNodeMetric($record->id)['status_label'] ?? 'offline')))
                    ->color(fn (string $state): string => $state === trans('monitoring::monitoring.tables.online') ? 'success' : 'danger'),

                TextColumn::make('cpu_usage')
                    ->label(trans('monitoring::monitoring.tables.cpu'))
                    ->getStateUsing(fn (Node $record): string => format_number($this->getNodeMetric($record->id)['cpu_usage_percent'] ?? 0, maxPrecision: 1) . ' %'),

                TextColumn::make('memory_usage')
                    ->label(trans('monitoring::monitoring.tables.memory'))
                    ->getStateUsing(fn (Node $record): string => convert_bytes_to_readable($this->getNodeMetric($record->id)['memory_used'] ?? 0) . ' / ' . convert_bytes_to_readable($this->getNodeMetric($record->id)['memory_total'] ?? 0)),

                TextColumn::make('disk_usage')
                    ->label(trans('monitoring::monitoring.tables.disk'))
                    ->getStateUsing(fn (Node $record): string => convert_bytes_to_readable($this->getNodeMetric($record->id)['disk_used'] ?? 0) . ' / ' . convert_bytes_to_readable($this->getNodeMetric($record->id)['disk_total'] ?? 0)),

                TextColumn::make('load_average')
                    ->label(trans('monitoring::monitoring.tables.load'))
                    ->getStateUsing(fn (Node $record): string => format_number($this->getNodeMetric($record->id)['load_average_1'] ?? 0, maxPrecision: 2) . ' / '
                        . format_number($this->getNodeMetric($record->id)['load_average_5'] ?? 0, maxPrecision: 2) . ' / '
                        . format_number($this->getNodeMetric($record->id)['load_average_15'] ?? 0, maxPrecision: 2)),

                TextColumn::make('servers_count')
                    ->label(trans('monitoring::monitoring.tables.servers'))
                    ->counts('servers')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->paginated(false);
    }
}
