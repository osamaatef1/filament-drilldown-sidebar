<?php

namespace OsamaAtef\DrilldownSidebar;

use Illuminate\Support\ServiceProvider;

class DrilldownSidebarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/views/components/sidebar' => resource_path('views/vendor/filament-panels/components/sidebar'),
        ], 'drilldown-sidebar-views');
    }
}
