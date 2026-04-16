<div>
    @php
        $navigation = filament()->getNavigation();
        $isRtl = __('filament-panels::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
        $hasNavigation = filament()->hasNavigation();
        $hasTopbar = filament()->hasTopbar();

        // Groups marked as drilled via DrilldownSidebarPlugin get drill-down navigation
        $drilledGroupLabels = filament('drilldown-sidebar')?->getDrilledGroups() ?? [];

        $drilldownGroups = collect($navigation)->filter(
            fn (\Filament\Navigation\NavigationGroup $group) =>
                filled($group->getLabel()) && count($group->getItems()) > 0
                && in_array($group->getLabel(), $drilledGroupLabels)
        );

        // Auto-drill to the active group on page load
        $activeNavGroup = $drilldownGroups
            ->first(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isActive() && filled($group->getLabel()));
        $activeGroupLabel = $activeNavGroup?->getLabel();
    @endphp

    {{-- format-ignore-start --}}
    <aside
        x-data="{}"
        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
            x-cloak
        @else
            x-cloak="-lg"
        @endif
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar"
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_START) }}

        <div class="fi-sidebar-header-ctn">
            <header class="fi-sidebar-header">
                @if ((! $hasTopbar) && $isSidebarCollapsibleOnDesktop)
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? 'heroicon-o-chevron-left' : 'heroicon-o-chevron-right'"
                        icon-size="lg"
                        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                        x-cloak
                        x-data="{}"
                        x-on:click="$store.sidebar.open()"
                        x-show="! $store.sidebar.isOpen"
                        class="fi-sidebar-open-collapse-sidebar-btn"
                    />
                @endif

                @if ((! $hasTopbar) && ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop))
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? 'heroicon-o-chevron-right' : 'heroicon-o-chevron-left'"
                        icon-size="lg"
                        :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                        x-cloak
                        x-data="{}"
                        x-on:click="$store.sidebar.close()"
                        x-show="$store.sidebar.isOpen"
                        class="fi-sidebar-close-collapse-sidebar-btn"
                    />
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}

                <div
                    @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-sidebar-header-logo-ctn"
                >
                    @if ($homeUrl = filament()->getHomeUrl())
                        <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                            <x-filament-panels::logo />
                        </a>
                    @else
                        <x-filament-panels::logo />
                    @endif
                </div>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
            </header>
        </div>

        @if (filament()->hasTenancy() && filament()->hasTenantMenu())
            <x-filament-panels::tenant-menu />
        @endif

        @if (filament()->isGlobalSearchEnabled() && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Sidebar)
            <div
                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
            >
                @livewire(Filament\Livewire\GlobalSearch::class)
            </div>
        @endif

        <nav class="fi-sidebar-nav">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

            {{-- ============================================================
                 Collapsed sidebar: original group dropdowns (icon-only mode)
                 ============================================================ --}}
            @if ($isSidebarCollapsibleOnDesktop)
                <ul
                    x-show="! $store.sidebar.isOpen"
                    class="fi-sidebar-nav-groups"
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
                @if ($isSidebarCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                    x-transition:enter="fi-transition-enter"
                    x-transition:enter-start="fi-transition-enter-start"
                    x-transition:enter-end="fi-transition-enter-end"
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
                class="fi-dd-nav-wrapper"
            >
                {{-- ========================
                     MAIN VIEW: group list
                     ======================== --}}
                <div
                    x-show="view === 'main'"
                    x-transition:enter="fi-dd-main-enter"
                    x-transition:enter-start="fi-dd-main-enter-start"
                    x-transition:enter-end="fi-dd-main-enter-end"
                    x-transition:leave="fi-dd-main-leave"
                    x-transition:leave-start="fi-dd-main-leave-start"
                    x-transition:leave-end="fi-dd-main-leave-end"
                >
                    {{-- Ungrouped items (Dashboard, etc.) --}}
                    <ul class="fi-sidebar-group-items">
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
                    <ul class="fi-sidebar-nav-groups fi-dd-groups-list">
                        @foreach ($navigation as $group)
                            @if (filled($group->getLabel()) && count($group->getItems()) > 0)
                                @if (in_array($group->getLabel(), $drilledGroupLabels))
                                    {{-- Drilldown group: clickable button with chevron --}}
                                    @php
                                        $groupButtonIcon = $group->getIcon() ?? collect($group->getItems())->first()?->getIcon();
                                    @endphp
                                    <li class="fi-sidebar-group fi-dd-group">
                                        <p class="fi-dd-group-label">
                                            {{ $group->getLabel() }}
                                        </p>
                                        <button
                                            type="button"
                                            x-on:click="goToGroup(@js($group->getLabel()))"
                                            @class([
                                                'fi-dd-group-btn',
                                                'fi-active' => $group->isActive(),
                                            ])
                                        >
                                            @if ($groupButtonIcon)
                                                <x-filament::icon
                                                    :icon="$groupButtonIcon"
                                                    class="fi-dd-btn-icon"
                                                />
                                            @endif
                                            <span class="fi-dd-btn-label">
                                                {{ $group->getLabel() }}
                                            </span>
                                            <x-filament::icon
                                                :icon="$isRtl ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right'"
                                                class="fi-dd-btn-chevron"
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
                    x-transition:enter="fi-dd-detail-enter"
                    x-transition:enter-start="fi-dd-detail-enter-start"
                    x-transition:enter-end="fi-dd-detail-enter-end"
                    x-transition:leave="fi-dd-detail-leave"
                    x-transition:leave-start="fi-dd-detail-leave-start"
                    x-transition:leave-end="fi-dd-detail-leave-end"
                >
                    {{-- Back button --}}
                    <button
                        type="button"
                        x-on:click="goBack()"
                        class="fi-dd-back-btn"
                    >
                        <x-filament::icon
                            :icon="$isRtl ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left'"
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
                                class="fi-dd-detail-panel"
                            >
                                {{-- Group title --}}
                                @php
                                    $detailIcon = $group->getIcon() ?? collect($group->getItems())->first()?->getIcon();
                                @endphp
                                <div class="fi-dd-detail-header">
                                    @if ($detailIcon)
                                        <x-filament::icon
                                            :icon="$detailIcon"
                                            class="fi-dd-detail-icon"
                                        />
                                    @endif
                                    <h3 class="fi-dd-detail-title">
                                        {{ $group->getLabel() }}
                                    </h3>
                                </div>

                                <hr class="fi-dd-detail-divider" />

                                {{-- Items --}}
                                <ul class="fi-dd-detail-items">
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
                        if (items) items.style.display = 'none'
                        group.classList.add('fi-collapsed')
                    })
            </script>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
        </nav>

        @php
            $isAuthenticated = filament()->auth()->check();
            $hasDatabaseNotificationsInSidebar = filament()->hasDatabaseNotifications() && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Sidebar;
            $hasUserMenuInSidebar = filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Sidebar;
            $shouldRenderFooter = $isAuthenticated && ($hasDatabaseNotificationsInSidebar || $hasUserMenuInSidebar);
        @endphp

        @if ($shouldRenderFooter)
            <div class="fi-sidebar-footer">
                @if ($hasDatabaseNotificationsInSidebar)
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if ($hasUserMenuInSidebar)
                    <x-filament-panels::user-menu />
                @endif
            </div>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
    </aside>
    {{-- format-ignore-end --}}

    <x-filament-actions::modals />
</div>
