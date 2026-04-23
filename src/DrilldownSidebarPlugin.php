<?php

namespace OsamaAtef\DrilldownSidebar;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use ReflectionClass;

class DrilldownSidebarPlugin implements Plugin
{
    protected array $drilledGroups = [];

    /** @var array<string, array<string>> Parent group label → child group labels */
    protected array $subGroups = [];

    protected bool $searchEnabled = false;

    /** @var array<string, array<string>>|null Request-scoped cache for auto-discovered map */
    protected ?array $discoveredSubGroups = null;

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
     * Manually declare which navigation groups are nested sub-groups of a parent group.
     *
     * When provided, this overrides auto-discovery. Otherwise the plugin reflects over
     * the panel's resources and builds the map from each resource's static
     * `$navigationParentGroup` property.
     *
     * Example:
     *   ->subGroups(['Lighthouse' => ['Activities', 'Insights']])
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
        if (! empty($this->subGroups)) {
            return $this->subGroups;
        }

        return $this->discoveredSubGroups ??= $this->discoverSubGroups();
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

    /**
     * Scan registered panel resources for a static `$navigationParentGroup`
     * property and build the [parent => [children]] map from it.
     *
     * Resources opt into nesting declaratively:
     *
     *     protected static ?string $navigationGroup = 'Activities';
     *     protected static ?string $navigationParentGroup = 'Lighthouse';
     *
     * @return array<string, array<string>>
     */
    protected function discoverSubGroups(): array
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return [];
        }

        $map = [];

        foreach ($panel->getResources() as $resourceClass) {
            try {
                $ref = new ReflectionClass($resourceClass);

                if (! $ref->hasProperty('navigationParentGroup')) {
                    continue;
                }

                $prop = $ref->getProperty('navigationParentGroup');
                $prop->setAccessible(true);
                $parent = $prop->getValue();

                if (! $parent) {
                    continue;
                }

                $child = $resourceClass::getNavigationGroup();

                if (! $child) {
                    continue;
                }

                $map[$parent] ??= [];

                if (! in_array($child, $map[$parent], true)) {
                    $map[$parent][] = $child;
                }
            } catch (\Throwable) {
                // Ignore resources that fail reflection (e.g. abstract, missing)
            }
        }

        return $map;
    }
}
