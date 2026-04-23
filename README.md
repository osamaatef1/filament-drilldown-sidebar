# Filament Drilldown Sidebar

A [Filament](https://filamentphp.com) plugin that turns selected sidebar groups into clickable buttons that slide into a detail view. Supports **inline nested sub-groups**, **auto-discovery from resources/pages**, and an optional **live search**.

Groups not marked for drill-down keep the standard Filament collapsible accordion. Both styles coexist in the same sidebar, rendered in their original registration order.

## Requirements

- PHP 8.1+
- Filament 3.x, 4.x, or 5.x
- Laravel 10, 11, or 12

## Version Compatibility

| Plugin Version | Filament | Laravel |
|---------------|----------|---------|
| 1.x           | 3.x, 4.x, 5.x | 10, 11, 12 |

The plugin automatically detects your Filament version and publishes the correct sidebar view:
- **Filament 3.x**: Blade component override (`components/sidebar/index.blade.php`)
- **Filament 4.x / 5.x**: Livewire view override (`livewire/sidebar.blade.php`)

> **After upgrading Filament to a new major version, re-publish the views:**
> ```bash
> php artisan vendor:publish --tag=drilldown-sidebar-views --force
> ```

## Installation

```bash
composer require osamaatef/filament-drilldown-sidebar
```

Publish the sidebar view:

```bash
php artisan vendor:publish --tag=drilldown-sidebar-views --force
```

> The `--force` flag is required because this plugin overrides Filament's default sidebar view.

## Basic Usage

Register the plugin in your panel provider and list the group labels that should use drill-down navigation:

```php
use OsamaAtef\DrilldownSidebar\DrilldownSidebarPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            DrilldownSidebarPlugin::make()
                ->drilledGroups([
                    'Service Management',
                    'Content Management',
                ]),
        ]);
}
```

Groups listed in `drilledGroups()` render as buttons with chevrons. Clicking one slides into a detail view with that group's items and a back button. All other groups render as the standard Filament collapsible accordion.

## Nested Sub-groups

A drilled parent group can contain nested sub-groups. Inside the drill detail, each sub-group renders as a native Filament collapsible `sidebar.group` — so the UI remains consistent with the rest of the panel.

### Option A — Declarative (recommended)

Declare the parent on each Resource / Page via a static `$navigationParentGroup` property. The plugin reflects over the panel's registered Resources and Pages and builds the hierarchy automatically.

```php
// app/Filament/Resources/WorkshopResource.php
class WorkshopResource extends Resource
{
    protected static ?string $navigationGroup = 'Activities';
    protected static ?string $navigationParentGroup = 'Lighthouse';
}

// app/Filament/Resources/SportResource.php
class SportResource extends Resource
{
    protected static ?string $navigationGroup = 'Activities';
    protected static ?string $navigationParentGroup = 'Lighthouse';
}
```

```php
// In AdminPanelProvider:
DrilldownSidebarPlugin::make()
    ->drilledGroups(['Lighthouse']);
```

Result: clicking **Lighthouse** opens a detail view containing an **Activities** sub-group with Workshop and Sport items nested inside.

### Option B — Explicit map (overrides auto-discovery)

```php
DrilldownSidebarPlugin::make()
    ->drilledGroups(['Lighthouse'])
    ->subGroups([
        'Lighthouse' => ['Activities', 'Bookings', 'Analytics'],
    ]);
```

When `subGroups()` is set, auto-discovery is skipped and your map wins.

### Virtual parent groups

A drilled parent doesn't need direct items — if **all** its content lives in sub-groups (none of its resources register `$navigationGroup` = the parent label directly), the plugin still renders the drill button. Its icon falls back to the first sub-group's icon.

## Ordering

Sub-groups are sorted by their position in your panel's `->navigationGroups([...])` array — the same list that orders top-level groups. Labels missing from that array fall to the end.

```php
->navigationGroups([
    'Lighthouse',
    'Activities',
    'Bookings',
    'Analytics',
    // ...
])
```

In the drill detail, children of `Lighthouse` will appear in this same order. If you want a different order, reorder the labels.

Manual `->subGroups([...])` config is used as-is (no re-sort).

## Live Search

Optional inline search input inside the drill detail view. Filters the parent's direct items by label (case-insensitive substring match).

```php
DrilldownSidebarPlugin::make()
    ->drilledGroups(['Service Management'])
    ->withSearch();      // enable
    // ->withSearch(false) to explicitly disable
```

Search is off by default.

## Auto-drill

If the current page belongs to a drilled group or any of its sub-groups, the sidebar opens that group's detail view automatically on page load — no extra wiring required.

## Collapsed sidebar

When the sidebar is collapsed to icon-only mode, all groups use Filament's default icon dropdown regardless of drill-down status.

## How It Works (summary)

- **Main view**: ungrouped items first, then labeled groups in registration order. Drilled groups show as buttons; standard groups show as collapsible accordions.
- **Detail view**: sliding panel with the group's title, icon, direct items, and any nested sub-groups rendered as native Filament collapsible groups. A back button returns to the main view.
- **Sub-groups are inline**, not a 3rd-level slide — the nav hierarchy stays two levels deep.

## Screenshot

![Sidebar Drilldown](art/cover.jpg)

## Changelog

- **1.4.3** — Sort auto-discovered sub-groups by panel `navigationGroups()` order.
- **1.4.2** — Render drill button + detail panel for parents that have only sub-groups (no direct items).
- **1.4.1** — Include panel Pages in sub-group auto-discovery (alongside Resources).
- **1.4.0** — Auto-discover sub-groups from `$navigationParentGroup` on Resources; sub-groups render inline as native Filament groups; redesigned drill detail header; fallback to native Filament rendering for non-drilled parents.
- **1.3.x** — Manual `->subGroups([...])` config, optional `->withSearch()` live search.
- **1.x** — Initial drill-down support.

## License

MIT License. See [LICENSE](LICENSE) for details.
