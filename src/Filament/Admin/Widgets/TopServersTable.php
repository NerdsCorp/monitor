<?php

namespace Pelican\Monitoring\Filament\Admin\Widgets;

use App\Models\Server;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopServersTable extends TableWidget
{
    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(Server::query()->with(['node', 'user']))
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
                    ->getStateUsing(function (Server $record) {
                        try {
                            return $record->retrieveStatus()->value;
                        } catch (\Exception) {
                            return 'error';
                        }
                    })
                    ->color(fn (string $state) => match ($state) {
                        'running' => 'success',
                        'starting' => 'warning',
                        'stopping' => 'warning',
                        'offline' => 'gray',
                        default => 'danger',
                    }),

                TextColumn::make('cpu_usage')
                    ->label(trans('monitoring::monitoring.tables.cpu'))
                    ->getStateUsing(function (Server $record) {
                        try {
                            $resources = $record->retrieveResources();
                            $cpu = $resources['cpu_absolute'] ?? 0;

                            return format_number(round($cpu, 1), maxPrecision: 1) . ' %';
                        } catch (\Exception) {
                            return '-';
                        }
                    }),

                TextColumn::make('memory_usage')
                    ->label(trans('monitoring::monitoring.tables.memory'))
                    ->getStateUsing(function (Server $record) {
                        try {
                            $resources = $record->retrieveResources();
                            $memory = $resources['memory_bytes'] ?? 0;

                            return $memory > 0 ? convert_bytes_to_readable($memory) : '-';
                        } catch (\Exception) {
                            return '-';
                        }
                    }),

                TextColumn::make('disk_usage')
                    ->label(trans('monitoring::monitoring.tables.disk'))
                    ->getStateUsing(function (Server $record) {
                        try {
                            $resources = $record->retrieveResources();
                            $disk = $resources['disk_bytes'] ?? 0;

                            return $disk > 0 ? convert_bytes_to_readable($disk) : '-';
                        } catch (\Exception) {
                            return '-';
                        }
                    }),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(10);
    }
}
