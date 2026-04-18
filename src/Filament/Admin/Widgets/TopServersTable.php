<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Pelican\Monitoring\Concerns\InteractsWithMonitoringData;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Pelican\Monitoring\Services\MonitoringDataService;

class TopServersTable extends TableWidget
{
    use InteractsWithMonitoringData;

    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Server::query()
                    ->with(['node:id,name', 'user:id,username'])
                    ->when(
                        blank($this->tableSortColumn ?? null),
                        fn (Builder $query) => $this->applyCachedSort($query, 'cpu_percent', 'desc'),
                    ),
            )
            ->heading(trans('monitoring::monitoring.tables.top_servers'))
            ->description(trans('monitoring::monitoring.tables.top_servers_desc'))
            ->columns([
                TextColumn::make('name')
                    ->label(trans('monitoring::monitoring.tables.name'))
                    ->sortable(),

                TextColumn::make('node.name')
                    ->label(trans('monitoring::monitoring.tables.node'))
                    ->sortable(),

                TextColumn::make('user.username')
                    ->label(trans('monitoring::monitoring.tables.owner'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(trans('monitoring::monitoring.tables.status'))
                    ->badge()
                    ->sortable(query: fn (Builder $query, string $direction) => $this->applyCachedSort($query, 'status_rank', $direction))
                    ->getStateUsing(fn (Server $record): string => trans('monitoring::monitoring.tables.server_status.' . ($this->getServerMetric($record->id)['status'] ?? 'error')))
                    ->color(fn (string $state) => match ($state) {
                        trans('monitoring::monitoring.tables.server_status.running') => 'success',
                        trans('monitoring::monitoring.tables.server_status.starting') => 'warning',
                        trans('monitoring::monitoring.tables.server_status.offline') => 'gray',
                        default => 'danger',
                    }),

                TextColumn::make('cpu_usage')
                    ->label(trans('monitoring::monitoring.tables.cpu'))
                    ->sortable(query: fn (Builder $query, string $direction) => $this->applyCachedSort($query, 'cpu_percent', $direction))
                    ->getStateUsing(fn (Server $record): string => format_number($this->getServerMetric($record->id)['cpu_percent'] ?? 0, maxPrecision: 1) . ' %'),

                TextColumn::make('memory_usage')
                    ->label(trans('monitoring::monitoring.tables.memory'))
                    ->sortable(query: fn (Builder $query, string $direction) => $this->applyCachedSort($query, 'memory_bytes', $direction))
                    ->getStateUsing(fn (Server $record): string => convert_bytes_to_readable($this->getServerMetric($record->id)['memory_bytes'] ?? 0)),

                TextColumn::make('disk_usage')
                    ->label(trans('monitoring::monitoring.tables.disk'))
                    ->sortable(query: fn (Builder $query, string $direction) => $this->applyCachedSort($query, 'disk_bytes', $direction))
                    ->getStateUsing(fn (Server $record): string => convert_bytes_to_readable($this->getServerMetric($record->id)['disk_bytes'] ?? 0)),
            ])
            ->filters([
                SelectFilter::make('node')
                    ->relationship('node', 'name')
                    ->label(trans('monitoring::monitoring.tables.node')),
                SelectFilter::make('user')
                    ->relationship('user', 'username')
                    ->label(trans('monitoring::monitoring.tables.owner')),
            ])
            ->defaultPaginationPageOption(10);
    }

    private function applyCachedSort(Builder $query, string $metric, string $direction): Builder
    {
        $orderedIds = app(MonitoringDataService::class)->getSortedServerIds($metric, $direction);

        if ($orderedIds === []) {
            return $query;
        }

        return $query->reorder()->orderByRaw($this->buildOrderExpression($query, $orderedIds));
    }

    private function buildOrderExpression(Builder $query, array $orderedIds): string
    {
        $qualifiedKeyName = $query->getModel()->getQualifiedKeyName();
        $cases = [];

        foreach (array_values($orderedIds) as $index => $serverId) {
            $cases[] = 'WHEN ' . (int) $serverId . ' THEN ' . $index;
        }

        return 'CASE ' . $qualifiedKeyName . ' ' . implode(' ', $cases) . ' ELSE ' . count($orderedIds) . ' END';
    }
}
