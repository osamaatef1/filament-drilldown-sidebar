@props([
    'navigation',
])

@php
    $openSidebarClasses = 'fi-sidebar-open w-[--sidebar-width] translate-x-0 shadow-xl ring-1 ring-gray-950/5 dark:ring-white/10 rtl:-translate-x-0';
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $sidebarCollapsible = filament()->isSidebarCollapsibleOnDesktop();

    // Groups marked as drilled via DrilldownSidebarPlugin get drill-down navigation
    $drilledGroupLabels = filament('drilldown-sidebar')?->getDrilledGroups() ?? [];

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

    // Auto-drill only applies to drilldown groups
    $activeNavGroup = $drilldownGroups
        ->first(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isActive() && filled($group->getLabel()));
    $activeGroupLabel = $activeNavGroup?->getLabel();
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
                goToGroup(label) {
                    this.activeGroup = label;
                    this.view = 'detail';
                },
                goBack() {
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
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-x-3"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-3"
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

                {{-- Spacing after ungrouped items --}}

                {{-- Labeled groups: rendered in original order, each as drilldown or standard --}}
                <ul class="fi-sidebar-nav-groups flex flex-col gap-y-7">
                    @foreach ($navigation as $group)
                        @if (filled($group->getLabel()) && count($group->getItems()) > 0)
                            @if (in_array($group->getLabel(), $drilledGroupLabels))
                                {{-- Drilldown group: clickable button with chevron --}}
                                @php
                                    $groupButtonIcon = $group->getIcon() ?? collect($group->getItems())->first()?->getIcon();
                                @endphp
                                <li class="fi-sidebar-group flex flex-col gap-y-1">
                                    <button
                                        type="button"
                                        x-on:click="goToGroup(@js($group->getLabel()))"
                                        @class([
                                            'flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm font-medium transition duration-75 outline-none',
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
                                            {{ $group->getLabel() }}
                                        </span>
                                        <x-filament::icon
                                            :icon="$isRtl ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right'"
                                            class="h-4 w-4 text-gray-400 dark:text-gray-500 shrink-0"
                                        />
                                    </button>
                                </li>
                            @else
                                {{-- Standard collapsible group --}}
                                @php
                                    $hasItemIcons = collect($group->getItems())->contains(fn ($item) => filled($item->getIcon()));
                                @endphp
                                <x-filament-panels::sidebar.group
                                    :active="$group->isActive()"
                                    :collapsible="$group->isCollapsible()"
                                    :icon="$hasItemIcons ? null : $group->getIcon()"
                                    :items="$group->getItems()"
                                    :label="$group->getLabel()"
                                    :attributes="\Filament\Support\prepare_inherited_attributes($group->getExtraSidebarAttributeBag())"
                                />
                            @endif
                        @endif
                    @endforeach
                </ul>
            </div>

            {{-- ==========================
                 DETAIL VIEW: group items
                 ========================== --}}
            <div
                x-show="view === 'detail'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-3"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-3"
            >
                {{-- Back button --}}
                <button
                    type="button"
                    x-on:click="goBack()"
                    class="fi-sidebar-back-btn flex items-center gap-x-2 rounded-lg px-2 py-2 text-sm font-medium text-gray-500 transition duration-75 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200 w-full outline-none focus-visible:bg-gray-100 dark:focus-visible:bg-white/5"
                >
                    <x-filament::icon
                        :icon="$isRtl ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left'"
                        class="h-4 w-4"
                    />
                    <span>{{ __('Back') }}</span>
                </button>

                {{-- Group detail panels (drilldown groups only) --}}
                @foreach ($navigation as $group)
                    @if (filled($group->getLabel())
                        && count($group->getItems()) > 0
                        && in_array($group->getLabel(), $drilledGroupLabels))
                        <div
                            x-show="activeGroup === @js($group->getLabel())"
                            x-cloak
                            class="fi-sidebar-detail-panel mt-2"
                        >
                            {{-- Group title --}}
                            @php
                                $detailIcon = $group->getIcon() ?? collect($group->getItems())->first()?->getIcon();
                            @endphp
                            <div class="flex items-center gap-x-3 px-2 pb-2">
                                @if ($detailIcon)
                                    <x-filament::icon
                                        :icon="$detailIcon"
                                        class="h-5 w-5 text-primary-500 dark:text-primary-400 shrink-0"
                                    />
                                @endif
                                <h3 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                                    {{ $group->getLabel() }}
                                </h3>
                            </div>

                            <hr class="border-primary-200 dark:border-primary-800 mx-1 mb-2" />

                            {{-- Items --}}
                            <ul class="flex flex-col gap-y-1">
                                @php
                                    $groupIcon = $group->getIcon();
                                @endphp
                                @foreach ($group->getItems() as $item)
                                    @php
                                        $itemIcon = $item->getIcon();
                                        $itemActiveIcon = $item->getActiveIcon();
                                        // If group has its own icon, suppress item icons (Filament convention)
                                        if ($groupIcon) {
                                            $itemIcon = null;
                                            $itemActiveIcon = null;
                                        }
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
