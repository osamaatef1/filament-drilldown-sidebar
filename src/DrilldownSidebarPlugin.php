<?php

namespace OsamaAtef\DrilldownSidebar;

use Filament\Contracts\Plugin;
use Filament\Panel;

class DrilldownSidebarPlugin implements Plugin
{
    protected array $drilledGroups = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'drilldown-sidebar';
    }

    /**
     * Set which navigation groups should use drill-down behavior.
     * Groups not listed here will use the standard Filament collapsible accordion.
     *
     * @param  array<string>  $groups  Navigation group labels to drill down.
     */
    public function drilledGroups(array $groups): static
    {
        $this->drilledGroups = $groups;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getDrilledGroups(): array
    {
        return $this->drilledGroups;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
