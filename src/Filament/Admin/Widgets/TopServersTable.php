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
        $orderedIds = app(MonitoringDataService::class)->getSortedServerIds('cpu_percent');

        return $table
            ->query(
                Server::query()
                    ->with(['node:id,name', 'user:id,username'])
                    ->when($orderedIds !== [], fn (Builder $query) => $query->orderByRaw($this->buildServerOrderExpression($query, $orderedIds))),
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
                    ->getStateUsing(fn (Server $record): string => trans('monitoring::monitoring.tables.server_status.' . ($this->getServerMetric($record->id)['status'] ?? 'error')))
                    ->color(fn (string $state) => match ($state) {
                        trans('monitoring::monitoring.tables.server_status.running') => 'success',
                        trans('monitoring::monitoring.tables.server_status.starting') => 'warning',
                        trans('monitoring::monitoring.tables.server_status.offline') => 'gray',
                        default => 'danger',
                    }),

                TextColumn::make('cpu_usage')
                    ->label(trans('monitoring::monitoring.tables.cpu'))
                    ->getStateUsing(fn (Server $record): string => format_number($this->getServerMetric($record->id)['cpu_percent'] ?? 0, maxPrecision: 1) . ' %'),

                TextColumn::make('memory_usage')
                    ->label(trans('monitoring::monitoring.tables.memory'))
                    ->getStateUsing(fn (Server $record): string => convert_bytes_to_readable($this->getServerMetric($record->id)['memory_bytes'] ?? 0)),

                TextColumn::make('disk_usage')
                    ->label(trans('monitoring::monitoring.tables.disk'))
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

    private function buildServerOrderExpression(Builder $query, array $orderedIds): string
    {
        $qualifiedKeyName = $query->getModel()->getQualifiedKeyName();
        $cases = [];

        foreach (array_values($orderedIds) as $index => $serverId) {
            $cases[] = 'WHEN ' . (int) $serverId . ' THEN ' . $index;
        }

        return 'CASE ' . $qualifiedKeyName . ' ' . implode(' ', $cases) . ' ELSE ' . count($orderedIds) . ' END';
    }
}
