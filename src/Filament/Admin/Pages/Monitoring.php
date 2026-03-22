<?php

namespace Pelican\Monitoring\Filament\Admin\Pages;

use App\Enums\TablerIcon;
use BackedEnum;
use Filament\Pages\Page;
use Pelican\Monitoring\Filament\Admin\Widgets\AllNodesCpuChart;
use Pelican\Monitoring\Filament\Admin\Widgets\AllNodesMemoryChart;
use Pelican\Monitoring\Filament\Admin\Widgets\AllNodesStorageChart;
use Pelican\Monitoring\Filament\Admin\Widgets\NodeHealthTable;
use Pelican\Monitoring\Filament\Admin\Widgets\SystemOverviewStats;
use Pelican\Monitoring\Filament\Admin\Widgets\TopServersTable;

class Monitoring extends Page
{
    protected static string|BackedEnum|null $navigationIcon = TablerIcon::Activity;

    protected static ?int $navigationSort = 2;

    protected string $view = 'monitoring::pages.monitoring';

    public static function getNavigationLabel(): string
    {
        return trans('monitoring::monitoring.navigation');
    }

    public function getTitle(): string
    {
        return trans('monitoring::monitoring.title');
    }

    public function getHeaderWidgets(): array
    {
        return [
            SystemOverviewStats::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            AllNodesCpuChart::class,
            AllNodesMemoryChart::class,
            AllNodesStorageChart::class,
            NodeHealthTable::class,
            TopServersTable::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
