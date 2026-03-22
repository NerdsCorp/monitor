<?php

namespace Pelican\Monitoring;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MonitoringPlugin implements Plugin
{
    public function getId(): string
    {
        return 'monitoring';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "Pelican\\Monitoring\\Filament\\$id\\Pages");
        $panel->discoverWidgets(plugin_path($this->getId(), "src/Filament/$id/Widgets"), "Pelican\\Monitoring\\Filament\\$id\\Widgets");
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
