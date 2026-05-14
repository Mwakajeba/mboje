<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div>
            <!-- <img src="{{ asset('assets/images/logo1.png') }}" width="120" style="color:#fff" alt="logo icon">  -->
            <h5 style="color:#fff">{{ \App\Services\SystemSettingService::get('app_name', 'SmartAccounting') }}</h5> 
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-first-page'></i></div>
    </div>

    <!--navigation-->
    <ul class="metismenu" id="menu">
        @foreach($menus as $menu)
            @php
                $isEditOrDelete = Str::contains($menu->route, ['edit', 'delete','destroy', 'create']);
            @endphp

            @if($menu->children->count())
                <li>
                    <a href="#" class="has-arrow" onclick="return false;">
                        <div class="parent-icon"><i class="{{ $menu->icon ?? 'bx bx-folder' }}"></i></div>
                        <div class="menu-title">{{ $menu->name }}</div>
                    </a>
                    <ul>
                        @php
                            // Optional explicit sort for Reports menu children
                            $children = $menu->name === 'Reports'
                                ? collect($menu->children)->sortBy(function($c) {
                                    $order = [
                                        'reports.index' => 1,
                                        'reports.accounting' => 2,
                                        'reports.inventory' => 3,
                                        'reports.production' => 4,
                                        'sales.reports.index' => 5,
                                        'reports.purchases' => 6,
                                    ];
                                    return $order[$c->route] ?? 999;
                                })
                                : $menu->children;
                        @endphp
                        @foreach($children as $child)
                            @php
                                $isChildEditOrDelete = Str::contains($child->route, ['edit', 'delete', 'destroy', 'create']);
                                $isHiddenReport = $menu->name === 'Reports' && $child->route === 'reports.customers';
                            @endphp

                            @if (!$isChildEditOrDelete && !$isHiddenReport)
                                <li>
                                    @php
                                        $resolvedRoute = $child->route === 'reports.purchases'
                                            ? 'purchases.reports.index'
                                            : $child->route;
                                    @endphp
                                    @if(Route::has($resolvedRoute))
                                        <a href="{{ route($resolvedRoute) }}">
                                            <i class="bx bx-right-arrow-alt"></i>{{ $child->name }}
                                        </a>
                                    @else
                                        <a href="#" class="text-muted" title="Route not yet implemented">
                                            <i class="bx bx-right-arrow-alt"></i>{{ $child->name }}
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @elseif(!$isEditOrDelete && $menu->route)
                <li>
                    @if(Route::has($menu->route))
                        <a href="{{ route($menu->route) }}">
                            <div class="parent-icon"><i class="{{ $menu->icon ?? 'bx bx-circle' }}"></i></div>
                            <div class="menu-title">{{ $menu->name }}</div>
                        </a>
                    @else
                        <a href="#" class="text-muted" title="Route not yet implemented">
                            <div class="parent-icon"><i class="{{ $menu->icon ?? 'bx bx-circle' }}"></i></div>
                            <div class="menu-title">{{ $menu->name }}</div>
                        </a>
                    @endif
                </li>
            @endif
        @endforeach
    </ul>
    <!--end navigation-->
</div>
