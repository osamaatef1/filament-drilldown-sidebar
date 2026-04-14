<?php

namespace OsamaAtef\DrilldownSidebar;

use Composer\InstalledVersions;
use Illuminate\Support\ServiceProvider;

class DrilldownSidebarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $majorVersion = $this->getFilamentMajorVersion();

        if ($majorVersion >= 4) {
            $this->publishes([
                __DIR__ . '/../resources/views/v4/livewire' => resource_path('views/vendor/filament-panels/livewire'),
            ], 'drilldown-sidebar-views');
        } else {
            $this->publishes([
                __DIR__ . '/../resources/views/v3/components/sidebar' => resource_path('views/vendor/filament-panels/components/sidebar'),
            ], 'drilldown-sidebar-views');
        }
    }

    protected function getFilamentMajorVersion(): int
    {
        try {
            $version = InstalledVersions::getVersion('filament/filament');

            return (int) explode('.', $version)[0];
        } catch (\Throwable) {
            return 3;
        }
    }
}
