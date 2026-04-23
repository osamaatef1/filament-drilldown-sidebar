<?php

namespace OsamaAtef\DrilldownSidebar;

use Filament\Contracts\Plugin;
use Filament\Panel;

class DrilldownSidebarPlugin implements Plugin
{
    protected array $drilledGroups = [];

    /** @var array<string, array<string>> Parent group label → child group labels */
    protected array $subGroups = [];

    protected bool $searchEnabled = false;

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

    /**
     * Define which navigation groups are nested sub-groups of a drilled group.
     * Clicking a sub-group in the detail view slides to a third-level panel.
     *
     * Example:
     *   ->subGroups(['Lighthouse' => ['Sports', 'Workshops', 'Packages']])
     *
     * @param  array<string, array<string>>  $groups
     */
    public function subGroups(array $groups): static
    {
        $this->subGroups = $groups;

        return $this;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getSubGroups(): array
    {
        return $this->subGroups;
    }

    /**
     * Enable or disable the live search input inside drill-down views.
     * Search is off by default; call ->withSearch() to enable it.
     */
    public function withSearch(bool $enabled = true): static
    {
        $this->searchEnabled = $enabled;

        return $this;
    }

    public function isSearchEnabled(): bool
    {
        return $this->searchEnabled;
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
