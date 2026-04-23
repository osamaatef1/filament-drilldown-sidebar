@props([
    'navigation',
])

@once
    <style>
        /* Indent items nested inside a drilldown sub-group so they read as children, not siblings of flat items */
        .fi-sidebar-nested-groups .fi-sidebar-group-items {
            padding-inline-start: 0.75rem;
            margin-inline-start: 1rem;
            border-inline-start: 1px solid rgb(229 231 235);
        }
        .dark .fi-sidebar-nested-groups .fi-sidebar-group-items {
            border-inline-start-color: rgba(255, 255, 255, 0.1);
        }
    </style>
@endonce

@php
    $openSidebarClasses = 'fi-sidebar-open w-[--sidebar-width] translate-x-0 shadow-xl ring-1 ring-gray-950/5 dark:ring-white/10 rtl:-translate-x-0';
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $sidebarCollapsible = filament()->isSidebarCollapsibleOnDesktop();

    // Groups marked as drilled via DrilldownSidebarPlugin get drill-down navigation
    try {
        $plugin             = filament('drilldown-sidebar');
        $drilledGroupLabels = $plugin?->getDrilledGroups() ?? [];
        $subGroupsMap       = $plugin?->getSubGroups() ?? [];
        $searchEnabled      = $plugin?->isSearchEnabled() ?? false;
    } catch (\Exception $e) {
        $drilledGroupLabels = [];
        $subGroupsMap       = [];
        $searchEnabled      = false;
    }

    $drilldownGroups = collect($navigation)->filter(
        fn (\Filament\Navigation\NavigationGroup $group) =>
            filled($group->getLabel()) && count($group->getItems()) > 0
            && in_array($group->getLabel(), $drilledGroupLabels)
    );

    $standardGroups = collect($navigation)->filter(
        fn (\Filament\Navigation\NavigationGroup $group) =>
            filled($group->getLabel()) && count($group->getItems()) > 0
            && ! in_array($group->getLabel(), $drilledGroupLabels)
    );

    // Build a quick label → NavigationGroup lookup for sub-group rendering
    $navigationByLabel = collect($navigation)->keyBy(fn ($g) => $g->getLabel());

    // Only hide child groups from the main view when their parent is actually a drilled group
    // (parents not in drilledGroups render as native Filament groups with their children as siblings).
    $allChildGroupLabels = collect($subGroupsMap)
        ->filter(fn ($children, $parent) => in_array($parent, $drilledGroupLabels))
        ->flatten()
        ->all();

    // Virtual drilled parents: drilled labels that aren't present in $navigation (because
    // no resource uses that label as its direct $navigationGroup) but have sub-groups defined.
    // These still need to render as drill buttons so the user can reach the nested children.
    $navigationLabels = collect($navigation)
        ->map(fn ($g) => $g->getLabel())
        ->filter()
        ->all();

    $virtualDrilledLabels = collect($drilledGroupLabels)
        ->filter(fn ($label) => ! in_array($label, $navigationLabels) && ! empty($subGroupsMap[$label] ?? []))
        ->values()
        ->all();

    // Resolve an icon for a drilled group, falling back to its first sub-group when the
    // group itself is virtual/empty.
    $resolveGroupIcon = function (?string $label) use ($navigationByLabel, $subGroupsMap) {
        if (! $label) {
            return null;
        }

        $navGroup = $navigationByLabel->get($label);
        if ($icon = $navGroup?->getIcon() ?? collect($navGroup?->getItems() ?? [])->first()?->getIcon()) {
            return $icon;
        }

        $firstChildLabel = collect($subGroupsMap[$label] ?? [])->first();
        if (! $firstChildLabel) {
            return null;
        }

        $childNavGroup = $navigationByLabel->get($firstChildLabel);

        return $childNavGroup?->getIcon()
            ?? collect($childNavGroup?->getItems() ?? [])->first()?->getIcon();
    };

    // Is any child of a virtual drilled parent currently active?
    $virtualDrilledIsActive = function (string $label) use ($navigationByLabel, $subGroupsMap) {
        return collect($subGroupsMap[$label] ?? [])
            ->contains(fn ($child) => $navigationByLabel->get($child)?->isActive() ?? false);
    };

    // Auto-drill: detect if the active page belongs to a drilled group or any of its sub-groups
    $activeNavGroup = $drilldownGroups
        ->first(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isActive() && filled($group->getLabel()));
    $activeGroupLabel = $activeNavGroup?->getLabel();

    if (! $activeGroupLabel) {
        foreach ($subGroupsMap as $parentLabel => $childLabels) {
            foreach ($childLabels as $childLabel) {
                if ($navigationByLabel->get($childLabel)?->isActive()) {
                    $activeGroupLabel = $parentLabel;
                    break 2;
                }
            }
        }
    }
@endphp

{{-- format-ignore-start --}}
<aside
    x-data="{}"
    @if ($sidebarCollapsible && (! filament()->hasTopNavigation()))
        x-cloak
        x-bind:class="
            $store.sidebar.isOpen
                ? @js($openSidebarClasses . ' ' . 'lg:sticky')
                : '-translate-x-full rtl:translate-x-full lg:sticky lg:translate-x-0 rtl:lg:-translate-x-0'
        "
    @else
        @if (filament()->hasTopNavigation())
            x-cloak
            x-bind:class="$store.sidebar.isOpen ? @js($openSidebarClasses) : '-translate-x-full rtl:translate-x-full'"
        @elseif (filament()->isSidebarFullyCollapsibleOnDesktop())
            x-cloak
            x-bind:class="$store.sidebar.isOpen ? @js($openSidebarClasses . ' ' . 'lg:sticky') : '-translate-x-full rtl:translate-x-full'"
        @else
            x-cloak="-lg"
            x-bind:class="
                $store.sidebar.isOpen
                    ? @js($openSidebarClasses . ' ' . 'lg:sticky')
                    : 'w-[--sidebar-width] -translate-x-full rtl:translate-x-full lg:sticky'
            "
        @endif
    @endif
    {{
        $attributes->class([
            'fi-sidebar fixed inset-y-0 start-0 z-30 flex flex-col h-screen content-start bg-white transition-all dark:bg-gray-900 lg:z-0 lg:bg-transparent lg:shadow-none lg:ring-0 lg:transition-none dark:lg:bg-transparent',
            'lg:translate-x-0 rtl:lg:-translate-x-0' => ! ($sidebarCollapsible || filament()->isSidebarFullyCollapsibleOnDesktop() || filament()->hasTopNavigation()),
            'lg:-translate-x-full rtl:lg:translate-x-full' => filament()->hasTopNavigation(),
        ])
    }}
>
    <div class="overflow-x-clip">
        <header
            class="fi-sidebar-header flex h-16 items-center bg-white px-6 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:shadow-sm"
        >
            <div
                @if ($sidebarCollapsible)
                    x-show="$store.sidebar.isOpen"
                    x-transition:enter="lg:transition lg:delay-100"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                @endif
            >
                @if ($homeUrl = filament()->getHomeUrl())
                    <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                        <x-filament-panels::logo />
                    </a>
                @else
                    <x-filament-panels::logo />
                @endif
            </div>

            @if ($sidebarCollapsible)
                <x-filament::icon-button
                    color="gray"
                    :icon="$isRtl ? 'heroicon-o-chevron-left' : 'heroicon-o-chevron-right'"
                    {{-- @deprecated Use `panels::sidebar.expand-button.rtl` instead of `panels::sidebar.expand-button` for RTL. --}}
                    :icon-alias="$isRtl ? ['panels::sidebar.expand-button.rtl', 'panels::sidebar.expand-button'] : 'panels::sidebar.expand-button'"
                    icon-size="lg"
                    :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                    x-cloak
                    x-data="{}"
                    x-on:click="$store.sidebar.open()"
                    x-show="! $store.sidebar.isOpen"
                    class="mx-auto"
                />
            @endif

            @if ($sidebarCollapsible || filament()->isSidebarFullyCollapsibleOnDesktop())
                <x-filament::icon-button
                    color="gray"
                    :icon="$isRtl ? 'heroicon-o-chevron-right' : 'heroicon-o-chevron-left'"
                    {{-- @deprecated Use `panels::sidebar.collapse-button.rtl` instead of `panels::sidebar.collapse-button` for RTL. --}}
                    :icon-alias="$isRtl ? ['panels::sidebar.collapse-button.rtl', 'panels::sidebar.collapse-button'] : 'panels::sidebar.collapse-button'"
                    icon-size="lg"
                    :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                    x-cloak
                    x-data="{}"
                    x-on:click="$store.sidebar.close()"
                    x-show="$store.sidebar.isOpen"
                    class="ms-auto hidden lg:flex"
                />
            @endif
        </header>
    </div>

    <nav
        class="fi-sidebar-nav flex-grow flex flex-col gap-y-7 overflow-y-auto overflow-x-hidden px-6 py-8"
        style="scrollbar-gutter: stable"
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

        @if (filament()->hasTenancy() && filament()->hasTenantMenu())
            <div
                @class([
                    'fi-sidebar-nav-tenant-menu-ctn',
                    '-mx-2' => ! $sidebarCollapsible,
                ])
                @if ($sidebarCollapsible)
                    x-bind:class="$store.sidebar.isOpen ? '-mx-2' : '-mx-4'"
                @endif
            >
                <x-filament-panels::tenant-menu />
            </div>
        @endif

        {{-- ============================================================
             Collapsed sidebar: original group dropdowns (icon-only mode)
             ============================================================ --}}
        @if ($sidebarCollapsible)
            <ul
                x-show="! $store.sidebar.isOpen"
                class="fi-sidebar-nav-groups -mx-2 flex flex-col gap-y-7"
            >
                @foreach ($navigation as $group)
                    <x-filament-panels::sidebar.group
                        :active="$group->isActive()"
                        :collapsible="$group->isCollapsible()"
                        :icon="$group->getIcon()"
                        :items="$group->getItems()"
                        :label="$group->getLabel()"
                        :attributes="\Filament\Support\prepare_inherited_attributes($group->getExtraSidebarAttributeBag())"
                    />
                @endforeach
            </ul>
        @endif

        {{-- ============================================================
             Expanded sidebar: standard groups + optional drill-down
             ============================================================ --}}
        <div
            @if ($sidebarCollapsible)
                x-show="$store.sidebar.isOpen"
                x-transition:enter="delay-100 lg:transition"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
            @endif
            x-data="{
                view: @js($activeGroupLabel ? 'detail' : 'main'),
                activeGroup: @js($activeGroupLabel),
                search: '',
                goToGroup(label) {
                    this.activeGroup = label;
                    this.search = '';
                    this.view = 'detail';
                },
                goBack() {
                    this.search = '';
                    this.activeGroup = null;
                    this.view = 'main';
                }
            }"
            class="-mx-2"
        >
            {{-- ========================
                 MAIN VIEW: group list
                 ======================== --}}
            <div
                x-show="view === 'main'"
                x-transition:enter="transition ease-out duration-250"
                x-transition:enter-start="opacity-0 -translate-x-4 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-x-4 scale-95"
            >
                {{-- Ungrouped items (Dashboard, etc.) --}}
                <ul class="flex flex-col gap-y-1">
                    @foreach ($navigation as $group)
                        @if (blank($group->getLabel()) && count($group->getItems()) > 0)
                            @foreach ($group->getItems() as $item)
                                <x-filament-panels::sidebar.item
                                    :active="$item->isActive()"
                                    :active-child-items="$item->isChildItemsActive()"
                                    :active-icon="$item->getActiveIcon()"
                                    :badge="$item->getBadge()"
                                    :badge-color="$item->getBadgeColor()"
                                    :badge-tooltip="$item->getBadgeTooltip()"
                                    :child-items="$item->getChildItems()"
                                    :first="$loop->first"
                                    :grouped="false"
                                    :icon="$item->getIcon()"
                                    :last="$loop->last"
                                    :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                                    :sidebar-collapsible="false"
                                    :url="$item->getUrl()"
                                >
                                    {{ $item->getLabel() }}

                                    @if ($item->getIcon() instanceof \Illuminate\Contracts\Support\Htmlable)
                                        <x-slot name="icon">
                                            {{ $item->getIcon() }}
                                        </x-slot>
                                    @endif
                                </x-filament-panels::sidebar.item>
                            @endforeach
                        @endif
                    @endforeach
                </ul>

                {{-- Labeled groups: rendered in original order, each as drilldown or standard --}}
                <ul class="fi-sidebar-nav-groups flex flex-col gap-y-7">
                    @foreach ($navigation as $group)
                        @php
                            $groupLabel = $group->getLabel();
                            $groupIsDrilled = filled($groupLabel) && in_array($groupLabel, $drilledGroupLabels);
                            $groupHasItems = count($group->getItems()) > 0;
                            $groupHasSubGroups = filled($groupLabel) && ! empty($subGroupsMap[$groupLabel] ?? []);
                        @endphp
                        @if (filled($groupLabel) && $groupIsDrilled && ($groupHasItems || $groupHasSubGroups))
                            {{-- Drilldown group: clickable button with chevron --}}
                            @php
                                $groupButtonIcon = $resolveGroupIcon($groupLabel);
                            @endphp
                            <li class="fi-sidebar-group flex flex-col gap-y-1 mt-2">
                                <p class="fi-sidebar-group-label text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-2 mb-1">
                                    {{ $groupLabel }}
                                </p>
                                <button
                                    type="button"
                                    x-on:click="goToGroup(@js($groupLabel))"
                                    @class([
                                        'flex w-full items-center gap-x-3 rounded-lg px-2 py-2.5 text-sm font-semibold transition duration-75 outline-none',
                                        'hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
                                        'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-white/5' => $group->isActive(),
                                        'text-gray-700 dark:text-gray-200' => ! $group->isActive(),
                                    ])
                                >
                                    @if ($groupButtonIcon)
                                        <x-filament::icon
                                            :icon="$groupButtonIcon"
                                            @class([
                                                'h-5 w-5 shrink-0',
                                                'text-primary-500 dark:text-primary-400' => $group->isActive(),
                                                'text-gray-400 dark:text-gray-500' => ! $group->isActive(),
                                            ])
                                        />
                                    @endif
                                    <span class="flex-1 truncate text-start">
                                        {{ $groupLabel }}
                                    </span>
                                    <x-filament::icon
                                        :icon="$isRtl ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right'"
                                        class="h-4 w-4 text-gray-400 dark:text-gray-500 shrink-0"
                                    />
                                </button>
                            </li>
                        @elseif (filled($groupLabel) && $groupHasItems && ! $groupIsDrilled && ! in_array($groupLabel, $allChildGroupLabels))
                            {{-- Standard collapsible group (skip child groups — they render inside the drilled detail) --}}
                            @php
                                $hasItemIcons = collect($group->getItems())->contains(fn ($item) => filled($item->getIcon()));
                            @endphp
                            <x-filament-panels::sidebar.group
                                :active="$group->isActive()"
                                :collapsible="$group->isCollapsible()"
                                :icon="$hasItemIcons ? null : $group->getIcon()"
                                :items="$group->getItems()"
                                :label="$groupLabel"
                                :attributes="\Filament\Support\prepare_inherited_attributes($group->getExtraSidebarAttributeBag())"
                            />
                        @endif
                    @endforeach

                    {{-- Virtual drill buttons: drilled labels not present in $navigation (all content lives in sub-groups) --}}
                    @foreach ($virtualDrilledLabels as $virtualLabel)
                        @php
                            $virtualIsActive = $virtualDrilledIsActive($virtualLabel);
                            $virtualIcon = $resolveGroupIcon($virtualLabel);
                        @endphp
                        <li class="fi-sidebar-group flex flex-col gap-y-1 mt-2">
                            <p class="fi-sidebar-group-label text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-2 mb-1">
                                {{ $virtualLabel }}
                            </p>
                            <button
                                type="button"
                                x-on:click="goToGroup(@js($virtualLabel))"
                                @class([
                                    'flex w-full items-center gap-x-3 rounded-lg px-2 py-2.5 text-sm font-semibold transition duration-75 outline-none',
                                    'hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
                                    'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-white/5' => $virtualIsActive,
                                    'text-gray-700 dark:text-gray-200' => ! $virtualIsActive,
                                ])
                            >
                                @if ($virtualIcon)
                                    <x-filament::icon
                                        :icon="$virtualIcon"
                                        @class([
                                            'h-5 w-5 shrink-0',
                                            'text-primary-500 dark:text-primary-400' => $virtualIsActive,
                                            'text-gray-400 dark:text-gray-500' => ! $virtualIsActive,
                                        ])
                                    />
                                @endif
                                <span class="flex-1 truncate text-start">{{ $virtualLabel }}</span>
                                <x-filament::icon
                                    :icon="$isRtl ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right'"
                                    class="h-4 w-4 text-gray-400 dark:text-gray-500 shrink-0"
                                />
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- ==========================
                 DETAIL VIEW: group items
                 ========================== --}}
            <div
                x-show="view === 'detail'"
                x-transition:enter="transition ease-out duration-250"
                x-transition:enter-start="opacity-0 translate-x-4 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-x-4 scale-95"
            >
                {{-- Group detail panels (drilldown groups only) --}}
                @foreach ($navigation as $group)
                    @php
                        $detailGroupLabel = $group->getLabel();
                        $detailGroupIsDrilled = filled($detailGroupLabel) && in_array($detailGroupLabel, $drilledGroupLabels);
                        $detailGroupHasItems = count($group->getItems()) > 0;
                        $detailGroupHasSubs = filled($detailGroupLabel) && ! empty($subGroupsMap[$detailGroupLabel] ?? []);
                    @endphp
                    @if ($detailGroupIsDrilled && ($detailGroupHasItems || $detailGroupHasSubs))
                        @php
                            $detailIcon = $resolveGroupIcon($detailGroupLabel);
                            $groupSubChildren = $subGroupsMap[$detailGroupLabel] ?? [];
                        @endphp
                        <div
                            x-show="activeGroup === @js($detailGroupLabel)"
                            x-cloak
                            class="fi-sidebar-detail-panel"
                        >
                            {{-- Detail header: small back chevron + static group label --}}
                            <div class="fi-sidebar-detail-header flex items-center gap-x-2 px-1 mb-4">
                                <button
                                    type="button"
                                    x-on:click="goBack()"
                                    :aria-label="@js(__('Back'))"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 dark:text-gray-500 transition duration-75 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200 outline-none focus-visible:bg-gray-100 dark:focus-visible:bg-white/5 shrink-0"
                                >
                                    <x-filament::icon
                                        :icon="$isRtl ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left'"
                                        class="h-4 w-4"
                                    />
                                </button>
                                @if ($detailIcon)
                                    <x-filament::icon
                                        :icon="$detailIcon"
                                        class="h-5 w-5 text-primary-500 dark:text-primary-400 shrink-0"
                                    />
                                @endif
                                <span class="flex-1 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $detailGroupLabel }}
                                </span>
                            </div>

                            {{-- Search input (when enabled) --}}
                            @if ($searchEnabled)
                                <div class="px-2 pb-3">
                                    <input
                                        type="text"
                                        x-model="search"
                                        placeholder="{{ __('Search...') }}"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700 placeholder-gray-400 outline-none focus:border-primary-400 focus:ring-1 focus:ring-primary-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-500"
                                    />
                                </div>
                            @endif

                            {{-- Sub-groups rendered inline as native Filament collapsible groups; items are indented with a tree-style guide --}}
                            @if (count($groupSubChildren) > 0)
                                <ul class="fi-sidebar-nav-groups fi-sidebar-nested-groups flex flex-col gap-y-7 mb-3">
                                    @foreach ($groupSubChildren as $childLabel)
                                        @php
                                            $childNavGroup = $navigationByLabel->get($childLabel);
                                        @endphp
                                        @if ($childNavGroup && count($childNavGroup->getItems()) > 0)
                                            <x-filament-panels::sidebar.group
                                                :active="$childNavGroup->isActive()"
                                                :collapsible="$childNavGroup->isCollapsible()"
                                                :icon="$childNavGroup->getIcon()"
                                                :items="$childNavGroup->getItems()"
                                                :label="$childNavGroup->getLabel()"
                                                :attributes="\Filament\Support\prepare_inherited_attributes($childNavGroup->getExtraSidebarAttributeBag())"
                                            />
                                        @endif
                                    @endforeach
                                </ul>

                                @if (collect($group->getItems())->isNotEmpty())
                                    <hr class="border-gray-100 dark:border-white/5 mx-1 mb-3" />
                                @endif
                            @endif

                            {{-- Direct items (flat items not belonging to a sub-group) --}}
                            <ul class="flex flex-col gap-y-1">
                                @php
                                    $groupIcon = $group->getIcon();
                                @endphp
                                @foreach ($group->getItems() as $item)
                                    @php
                                        $itemIcon = $item->getIcon();
                                        $itemActiveIcon = $item->getActiveIcon();
                                        if ($groupIcon) {
                                            $itemIcon = null;
                                            $itemActiveIcon = null;
                                        }
                                        $itemXShow = $searchEnabled
                                            ? "search === '' || '" . addslashes(strtolower($item->getLabel())) . "'.includes(search.toLowerCase())"
                                            : 'true';
                                    @endphp
                                    <x-filament-panels::sidebar.item
                                        :active="$item->isActive()"
                                        :active-child-items="$item->isChildItemsActive()"
                                        :active-icon="$itemActiveIcon"
                                        :badge="$item->getBadge()"
                                        :badge-color="$item->getBadgeColor()"
                                        :badge-tooltip="$item->getBadgeTooltip()"
                                        :child-items="$item->getChildItems()"
                                        :first="$loop->first"
                                        :grouped="true"
                                        :icon="$itemIcon"
                                        :last="$loop->last"
                                        :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                                        :sidebar-collapsible="false"
                                        :url="$item->getUrl()"
                                        x-show="{{ $itemXShow }}"
                                    >
                                        {{ $item->getLabel() }}

                                        @if ($itemIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                            <x-slot name="icon">
                                                {{ $itemIcon }}
                                            </x-slot>
                                        @endif

                                        @if ($itemActiveIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                            <x-slot name="activeIcon">
                                                {{ $itemActiveIcon }}
                                            </x-slot>
                                        @endif
                                    </x-filament-panels::sidebar.item>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach

                {{-- Virtual detail panels: drilled labels not in $navigation (all content lives in sub-groups) --}}
                @foreach ($virtualDrilledLabels as $virtualLabel)
                    @php
                        $groupSubChildren = $subGroupsMap[$virtualLabel] ?? [];
                        $detailIcon = $resolveGroupIcon($virtualLabel);
                    @endphp
                    <div
                        x-show="activeGroup === @js($virtualLabel)"
                        x-cloak
                        class="fi-sidebar-detail-panel"
                    >
                        <div class="fi-sidebar-detail-header flex items-center gap-x-2 px-1 mb-4">
                            <button
                                type="button"
                                x-on:click="goBack()"
                                :aria-label="@js(__('Back'))"
                                class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 dark:text-gray-500 transition duration-75 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200 outline-none focus-visible:bg-gray-100 dark:focus-visible:bg-white/5 shrink-0"
                            >
                                <x-filament::icon
                                    :icon="$isRtl ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left'"
                                    class="h-4 w-4"
                                />
                            </button>
                            @if ($detailIcon)
                                <x-filament::icon
                                    :icon="$detailIcon"
                                    class="h-5 w-5 text-primary-500 dark:text-primary-400 shrink-0"
                                />
                            @endif
                            <span class="flex-1 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $virtualLabel }}
                            </span>
                        </div>

                        @if ($searchEnabled)
                            <div class="px-2 pb-3">
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="{{ __('Search...') }}"
                                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700 placeholder-gray-400 outline-none focus:border-primary-400 focus:ring-1 focus:ring-primary-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-500"
                                />
                            </div>
                        @endif

                        @if (count($groupSubChildren) > 0)
                            <ul class="fi-sidebar-nav-groups fi-sidebar-nested-groups flex flex-col gap-y-7 mb-3">
                                @foreach ($groupSubChildren as $childLabel)
                                    @php
                                        $childNavGroup = $navigationByLabel->get($childLabel);
                                    @endphp
                                    @if ($childNavGroup && count($childNavGroup->getItems()) > 0)
                                        <x-filament-panels::sidebar.group
                                            :active="$childNavGroup->isActive()"
                                            :collapsible="$childNavGroup->isCollapsible()"
                                            :icon="$childNavGroup->getIcon()"
                                            :items="$childNavGroup->getItems()"
                                            :label="$childNavGroup->getLabel()"
                                            :attributes="\Filament\Support\prepare_inherited_attributes($childNavGroup->getExtraSidebarAttributeBag())"
                                        />
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>

        {{-- Initialize collapsedGroups localStorage for standard collapsible groups --}}
        <script>
            var collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups'))

            if (collapsedGroups === null || collapsedGroups === 'null') {
                localStorage.setItem(
                    'collapsedGroups',
                    JSON.stringify(@js(
                        collect($navigation)
                            ->filter(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isCollapsed())
                            ->map(fn (\Filament\Navigation\NavigationGroup $group): string => $group->getLabel())
                            ->values()
                            ->all()
                    )),
                )
            }

            collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups'))

            document
                .querySelectorAll('.fi-sidebar-group')
                .forEach((group) => {
                    if (
                        !collapsedGroups.includes(group.dataset.groupLabel)
                    ) {
                        return
                    }

                    var items = group.querySelector('.fi-sidebar-group-items')
                    var collapseBtn = group.querySelector('.fi-sidebar-group-collapse-button')
                    if (items) items.style.display = 'none'
                    if (collapseBtn) collapseBtn.classList.add('-rotate-180')
                })
        </script>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
    </nav>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
</aside>
{{-- format-ignore-end --}}
