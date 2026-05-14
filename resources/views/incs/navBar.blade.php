<header>
    @php
        // Check subscription expiry for current user's company
        $subscriptionWarning = null;
        if (auth()->check() && auth()->user()->company_id) {
            $activeSubscription = \App\Models\Subscription::where('company_id', auth()->user()->company_id)
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->first();
            
            if ($activeSubscription) {
                $timeRemaining = $activeSubscription->getFormattedTimeRemaining();
                $notificationDays = $activeSubscription->features['notification_days'] ?? \App\Services\SystemSettingService::get('subscription_notification_days_30', 30);
                
                // Show warning if within notification days or expired
                if ($timeRemaining['status'] === 'expired' || 
                    ($timeRemaining['status'] === 'warning' && $activeSubscription->daysUntilExpiry() <= $notificationDays) ||
                    $timeRemaining['status'] === 'danger') {
                    $subscriptionWarning = $timeRemaining;
                }
            }
        }
    @endphp
    
    @if($subscriptionWarning)
    <!-- Subscription Expiry Warning Marquee -->
    <div class="subscription-warning-bar bg-{{ $subscriptionWarning['status'] === 'expired' ? 'danger' : ($subscriptionWarning['status'] === 'danger' ? 'danger' : 'warning') }} text-white" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1050; height: 40px; line-height: 40px;">
        <marquee behavior="scroll" direction="left" scrollamount="3" onmouseover="this.stop();" onmouseout="this.start();" style="height: 40px; line-height: 40px;">
            <div class="d-inline-flex align-items-center" style="padding: 0 20px;">
                <i class="bx bx-error-circle fs-5 me-2"></i>
                <strong>SUBSCRIPTION ALERT:</strong>
                <span class="ms-2">
                    @if($subscriptionWarning['status'] === 'expired')
                        Your subscription has EXPIRED! Please contact your administrator immediately to renew your subscription. 
                        <span class="ms-2">Contact: <a href="tel:+255747762244" class="text-white text-decoration-underline fw-bold">+255 747 762 244</a></span>
                    @else
                        Your subscription will expire in <span id="subscription-countdown" data-end-date="{{ $subscriptionWarning['end_date_iso'] ?? $activeSubscription->end_date->toIso8601String() }}">{{ $subscriptionWarning['formatted'] }}</span>. Please renew to avoid service interruption. 
                        <span class="ms-2">Need help? Contact: <a href="tel:+255747762244" class="text-white text-decoration-underline fw-bold">+255 747 762 244</a></span>
                    @endif
                </span>
            </div>
        </marquee>
    </div>
    @endif
    
    <div class="topbar d-flex align-items-center" style="{{ $subscriptionWarning ? 'top: 40px;' : '' }}">
        <nav class="navbar navbar-expand gap-3">
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
            </div>
            <div class="top-menu-left d-none d-lg-block">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="app-emailbox.html"><i class='bx bx-envelope'></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="app-chat-box.html"><i class='bx bx-message'></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="app-fullcalender.html"><i class='bx bx-calendar'></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="app-to-do.html"><i class='bx bx-check-square'></i></a>
                    </li>
                </ul>
            </div>
            <div class="search-bar flex-grow-1">
                <div class="position-relative search-bar-box">
                    <form id="global-search-form" onsubmit="return false;">
                        <input type="text" id="global-search-input" class="form-control search-control" autofocus placeholder="Type to search..." autocomplete="off"> 
                        <span class="position-absolute top-50 search-show translate-middle-y"><i class='bx bx-search'></i></span>
                        <span class="position-absolute top-50 search-close translate-middle-y"><i class='bx bx-x'></i></span>
                        <div id="global-search-results" class="dropdown-menu dropdown-menu-end global-search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 400px; overflow-y: auto; z-index: 1050; margin-top: 5px; min-width: 100%;">
                            <div class="search-results-content"></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="top-menu ms-auto">
                <ul class="navbar-nav align-items-center gap-1">
                    <li class="nav-item mobile-search-icon">
                        <a class="nav-link" href="javascript:;" onclick="toggleMobileSearch()"><i class='bx bx-search'></i>
                        </a>
                    </li>
                    <li class="nav-item dark-mode d-none d-sm-flex">
                        <a class="nav-link dark-mode-icon" href="javascript:;"><i class='bx bx-moon'></i>
                        </a>
                    </li>
                                      <li class="nav-item dropdown dropdown-large">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> <i class='bx bx-category'></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <div class="row row-cols-3 g-3 p-3">
                                <div class="col text-center">
                                    <a href="{{ route('sales.invoices.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-cosmic"><i class='bx bx-receipt'></i>
                                        </div>
                                        <div class="app-title">Sales Invoice</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('sales.pos.index') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-lush"><i class='bx bx-shopping-bag'></i>
                                        </div>
                                        <div class="app-title">POS Sales</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('purchases.purchase-invoices.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-burning"><i class='bx bx-purchase-tag'></i>
                                        </div>
                                        <div class="app-title">Purchase Invoice</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('sales.orders.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-kyoto"><i class='bx bx-cart'></i>
                                        </div>
                                        <div class="app-title">Sales Order</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('purchases.orders.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-blues"><i class='bx bx-shopping-bag'></i>
                                        </div>
                                        <div class="app-title">Purchase Order</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('accounting.receipt-vouchers.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-moonlit"><i class='bx bx-money'></i>
                                    </div>
                                        <div class="app-title">Receipt Voucher</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('accounting.payment-vouchers.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-lush"><i class='bx bx-credit-card'></i>
                                    </div>
                                        <div class="app-title">Payment Voucher</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('inventory.transfers.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-Ohhappiness"><i class='bx bx-transfer'></i>
                                    </div>
                                        <div class="app-title">Stock Transfer</div>
                                    </a>
                                </div>
                                <div class="col text-center">
                                    <a href="{{ route('inventory.items.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-cosmic"><i class='bx bx-package'></i>
							</div>
                                        <div class="app-title">Add Product</div>
                                    </a>
						</div>
						<div class="col text-center">
                                    <a href="{{ route('customers.create') }}" class="text-decoration-none">
                                        <div class="app-box mx-auto bg-gradient-burning"><i class='bx bx-user-plus'></i>
                                        </div>
                                        <div class="app-title">Add Customer</div>
                                    </a>
							</div>
                            </div>
                        </div>
                    </li>
                    @php
                    $today = \Carbon\Carbon::today()->toDateString();
                    // Removed loan_schedules query - loan functionality not available
                    $dueSchedules = collect([]);
                    
                    // Get overdue sales invoices
                    $overdueInvoices = collect([]);
                    if (auth()->check()) {
                        $overdueInvoices = \App\Models\Sales\SalesInvoice::with(['customer'])
                            ->whereHas('customer', function ($query) {
                                $query->whereHas('branch', function ($q) {
                                    $q->where('company_id', auth()->user()->company_id);
                                });
                            })
                            ->when(auth()->user()->branch_id, function ($query) {
                                return $query->where('branch_id', auth()->user()->branch_id);
                            })
                            ->where('status', 'sent')
                            ->where('due_date', '<', $today)
                            ->where('balance_due', '>', 0)
                            ->orderBy('due_date', 'asc')
                            ->limit(10)
                            ->get();
                    }

                    // Get expiring items (within 30 days)
                    $company = auth()->check() ? auth()->user()->company : null;
                    $expiringItems = collect([]);
                    $expiringItemsCount = 0;
                    if ($company) {
                        $warningDays = (int) (\App\Models\SystemSetting::where('key', 'inventory_expiry_warning_days')
                            ->value('value') ?? 30);
                        $warningDate = now()->addDays($warningDays)->toDateString();
                        
                        // Get total count of expiring items for notification count
                        $expiringItemsCount = \App\Models\Inventory\ExpiryTracking::whereHas('item', function($query) use ($company) {
                                $query->where('company_id', $company->id)
                                      ->where('track_expiry', true);
                            })
                            ->whereHas('location', function($query) {
                                if (auth()->check() && auth()->user()->branch_id) {
                                    $query->where('branch_id', auth()->user()->branch_id);
                                }
                            })
                            ->where('expiry_date', '<=', $warningDate)
                            ->where('expiry_date', '>=', $today)
                            ->where('quantity', '>', 0)
                            ->count();

                        // Get limited items for dropdown display
                        $expiringItems = \App\Models\Inventory\ExpiryTracking::with(['item', 'location'])
                            ->whereHas('item', function($query) use ($company) {
                                $query->where('company_id', $company->id)
                                      ->where('track_expiry', true);
                            })
                            ->whereHas('location', function($query) {
                                if (auth()->check() && auth()->user()->branch_id) {
                                    $query->where('branch_id', auth()->user()->branch_id);
                                }
                            })
                            ->where('expiry_date', '<=', $warningDate)
                            ->where('expiry_date', '>=', $today)
                            ->where('quantity', '>', 0)
                            ->orderBy('expiry_date', 'asc')
                            ->limit(5)
                            ->get();
                    }
                    @endphp
                    <li class="nav-item dropdown dropdown-large">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> <span class="alert-count" id="navbarNotificationCount">{{$dueSchedules->count() + $expiringItemsCount}}</span>
                            <i class='bx bx-bell'></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:;">
                                <div class="msg-header">
                                    <p class="msg-header-title">Notifications</p>
                                    <!-- <p class="msg-header-clear ms-auto">Marks all as read</p> -->
                                </div>
                            </a>
                            <div class="header-notifications-list">
                                @if($dueSchedules->count())
                                @foreach($dueSchedules as $due)
                                <a class="dropdown-item" href="javascript:;">
                                    <div class="d-flex align-items-center">
                                        <div class="notify bg-light-warning text-warning"><i class="bx bx-user"></i></div>
                                        <div class="flex-grow-1">
                                            <h6 class="msg-name">{{ $due->name }} <span class="msg-time float-end">Due Today</span></h6>
                                            <p class="msg-info">Amount: {{ number_format($due->amount_due, 2) }}</p>
                                        </div>
                                </a>
                                @endforeach
                                @endif

                                <!-- Expiry Alerts Section -->
                                @if($expiringItems->count() > 0)
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-header">
                                    <small class="text-muted">Expiry Alerts</small>
                                </div>
                                @foreach($expiringItems as $item)
                                <a class="dropdown-item" href="{{ route('inventory.items.index') }}?search={{ $item->item->name }}">
                                    <div class="d-flex align-items-center">
                                        <div class="notify bg-light-warning text-warning">
                                            <i class="bx bx-package"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="msg-name">{{ $item->item->name }} 
                                                <span class="msg-time float-end">
                                                    {{ (int) abs(now()->diffInDays($item->expiry_date)) }} days
                                                </span>
                                            </h6>
                                            <p class="msg-info">Batch: {{ $item->batch_number }} - Expires: {{ \Carbon\Carbon::parse($item->expiry_date)->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                </a>
                                @endforeach
                                @endif


                            </div>
                            <a href="{{ route('expiry-alerts') }}">
                                <div class="text-center msg-footer">View All Notifications</div>
                            </a>
                        </div>
                    </li>
                    <li class="nav-item dropdown dropdown-large">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> <span class="alert-count">{{ $overdueInvoices->count() }}</span>
                            <i class='bx bx-receipt'></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:;">
                                <div class="msg-header">
                                    <p class="msg-header-title">Overdue Sales Invoices</p>
                                    <p class="msg-header-clear ms-auto">Total: {{ number_format($overdueInvoices->sum('balance_due'), 2) }}</p>
                                </div>
                            </a>
                            <div class="header-message-list">
                                @if($overdueInvoices->count())
                                    @foreach($overdueInvoices as $invoice)
                                        <a class="dropdown-item" href="{{ route('sales.invoices.show', $invoice->encoded_id) }}">
                                    <div class="d-flex align-items-center">
                                                <div class="notify bg-light-danger text-danger">
                                                    <i class="bx bx-receipt"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                                    <h6 class="msg-name">{{ $invoice->invoice_number }} <span class="msg-time float-end">{{ \Carbon\Carbon::parse($invoice->due_date)->diffForHumans() }}</span></h6>
                                                    <p class="msg-info">{{ $invoice->customer->name ?? 'Unknown Customer' }} - {{ number_format($invoice->balance_due, 2) }}</p>
                                        </div>
                                    </div>
                                </a>
                                    @endforeach
                                @else
                                <a class="dropdown-item" href="javascript:;">
                                    <div class="d-flex align-items-center">
                                            <div class="notify bg-light-success text-success">
                                                <i class="bx bx-check-circle"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                                <h6 class="msg-name">No overdue invoices</h6>
                                                <p class="msg-info">All invoices are up to date</p>
                                        </div>
                                    </div>
                                </a>
                                @endif
                            </div>
                            <a href="{{ route('sales.invoices.index') }}">
                                <div class="text-center msg-footer">View All Sales Invoices</div>
                            </a>
                        </div>
                    </li>
                    <!-- Mobile Logout Shortcut -->
                    <li class="nav-item d-inline-flex d-lg-none">
                        <a class="nav-link" href="#" title="Logout"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class='bx bx-log-out-circle'></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Language Switcher -->
            <div class="me-3">
                @include('incs.languageSwitcher')
            </div>

            <div class="user-box dropdown px-3">
                <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ asset('assets/images/avatars/default.png') }}" class="user-img" alt="user avatar">
                    <div class="user-info ps-3">
                        <p class="user-name mb-0">{{ Auth::user()->name ?? 'User' }}</p>
                        <?php
                        // Fetch the user's role name
                        $user = Auth::user();
                        $userRoles = $user ? ($user->roles ?? collect()) : collect();
                        $roleName = $userRoles->first() ? ucfirst($userRoles->first()->name) : '';
                        
                        // Get current branch (same logic as dashboard)
                        $currentBranch = '';
                        if ($user && session('branch_id')) {
                            $currentBranch = (optional(optional($user->branches)->firstWhere('id', session('branch_id')))->name ?? ($user->branch->name ?? 'N/A'));
                        } elseif ($user) {
                            $currentBranch = $user->branch->name ?? '';
                        }
                        
                        // Get current location (same logic as dashboard)
                        $currentLocation = '';
                        if (session('location_id')) {
                            $currentLocation = optional(\App\Models\InventoryLocation::find(session('location_id')))->name ?? '';
                        }
                        
                        // Build location info string
                        $locationInfo = '';
                        if ($currentBranch || $currentLocation) {
                            $parts = array_filter([$currentBranch, $currentLocation]);
                            $locationInfo = ' ' . implode(' - ', $parts);
                        }
                        ?>
                        <p class="designattion mb-0">{{ $locationInfo }}</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('users.profile') }}"><i class="bx bx-user"></i><span>Profile</span></a>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('users.profile') }}"><i class='bx bx-home-circle'></i><span>Change Password</span></a>
                    </li>
                    <li>
                        <div class="dropdown-divider mb-0"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class='bx bx-log-out-circle'></i><span> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>
<form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
    @csrf
</form>

<style>
    .subscription-warning-bar {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        font-size: 14px;
    }
    .subscription-warning-bar a:hover {
        text-decoration: underline !important;
        opacity: 0.9;
    }
    @if($subscriptionWarning)
    .page-wrapper {
        margin-top: 100px !important;
    }
    @endif
</style>

@if($subscriptionWarning && $subscriptionWarning['status'] !== 'expired')
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    const countdownElement = document.getElementById('subscription-countdown');
    if (!countdownElement) return;
    
    const endDateStr = countdownElement.getAttribute('data-end-date');
    if (!endDateStr) return;
    
    const endDate = new Date(endDateStr);
    
    function updateCountdown() {
        const now = new Date();
        const diff = endDate - now;
        
        if (diff <= 0) {
            countdownElement.textContent = 'EXPIRED';
            return;
        }
        
        // Calculate time components
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        // Build formatted string
        const parts = [];
        if (days > 0) {
            parts.push(days + ' day' + (days !== 1 ? 's' : ''));
        }
        if (hours > 0 || days > 0) {
            parts.push(hours + ' hour' + (hours !== 1 ? 's' : ''));
        }
        if (minutes > 0 || hours > 0 || days > 0) {
            parts.push(minutes + ' minute' + (minutes !== 1 ? 's' : ''));
        }
        parts.push(seconds + ' second' + (seconds !== 1 ? 's' : ''));
        
        countdownElement.textContent = parts.join(', ') + ' remaining';
    }
    
    // Update immediately
    updateCountdown();
    
    // Update every second
    setInterval(updateCountdown, 1000);
})();
</script>
@endif

<script nonce="{{ $cspNonce ?? '' }}">
let searchTimeout;
const searchInput = document.getElementById('global-search-input');
const searchResults = document.getElementById('global-search-results');
const resultsContent = searchResults?.querySelector('.search-results-content');

function toggleMobileSearch() {
    const searchBar = document.querySelector('.search-bar');
    if (searchBar) {
        searchBar.classList.toggle('d-none');
        if (!searchBar.classList.contains('d-none')) {
            setTimeout(() => {
                searchInput?.focus();
            }, 100);
        }
    }
}

if (searchInput && searchResults && resultsContent) {
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Hide results if query is too short
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        // Debounce search
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Show results when input is focused and has content
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            performSearch(this.value.trim());
        }
    });
}

function performSearch(query) {
    if (!query || query.length < 2) {
        if (searchResults) searchResults.style.display = 'none';
        return;
    }
    
    if (!resultsContent) return;
    
    // Show loading state
    resultsContent.innerHTML = '<div class="p-3 text-center"><i class="bx bx-loader-alt bx-spin"></i> Searching...</div>';
    if (searchResults) searchResults.style.display = 'block';
    
    fetch(`{{ route('global-search') }}?q=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(async response => {
        if (!response.ok) {
            let errorMessage = 'Network response was not ok';
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorData.error || errorMessage;
            } catch (e) {
                // If response is not JSON, use status text
                errorMessage = response.statusText || errorMessage;
            }
            throw new Error(errorMessage);
        }
        return response.json();
    })
    .then(data => {
        if (data && Array.isArray(data.results)) {
            displayResults(data.results, query);
        } else {
            throw new Error('Invalid response format');
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (resultsContent) {
            const errorMsg = error.message || 'Error performing search. Please try again.';
            resultsContent.innerHTML = `<div class="p-3 text-danger"><i class="bx bx-error-circle me-2"></i>${escapeHtml(errorMsg)}</div>`;
        }
        if (searchResults) {
            searchResults.style.display = 'block';
        }
    });
}

function displayResults(results, query) {
    if (!resultsContent || !searchResults) return;
    
    // Validate results is an array
    if (!results || !Array.isArray(results)) {
        if (resultsContent) {
            resultsContent.innerHTML = '<div class="p-3 text-danger">Invalid search results. Please try again.</div>';
        }
        if (searchResults) {
            searchResults.style.display = 'block';
        }
        return;
    }
    
    if (results.length === 0) {
        resultsContent.innerHTML = `
            <div class="p-3 text-center text-muted">
                <i class="bx bx-search-alt-2 fs-4 mb-2"></i>
                <p class="mb-0">No results found for "${escapeHtml(query)}"</p>
            </div>
        `;
        searchResults.style.display = 'block';
        return;
    }
    
    // Group results by type
    const grouped = {};
    results.forEach(result => {
        if (!grouped[result.type]) {
            grouped[result.type] = [];
        }
        grouped[result.type].push(result);
    });
    
    // Type labels
    const typeLabels = {
        'page': 'Pages',
        'menu': 'Menu Items',
        'customer': 'Customers',
        'sales_invoice': 'Sales Invoices',
        'sales_order': 'Sales Orders',
        'pos_sale': 'POS Sales',
        'cash_sale': 'Cash Sales',
        'item': 'Inventory Items',
        'supplier': 'Suppliers',
        'purchase_invoice': 'Purchase Invoices',
        'receipt': 'Receipts',
        'payment_voucher': 'Payment Vouchers',
        'bill': 'Bills'
    };
    
    // Sort results: pages first, then menus, then other results
    results.sort((a, b) => {
        if (a.type === 'page' && b.type !== 'page') return -1;
        if (a.type !== 'page' && b.type === 'page') return 1;
        if (a.type === 'menu' && b.type !== 'menu' && b.type !== 'page') return -1;
        if (a.type !== 'menu' && b.type === 'menu' && a.type !== 'page') return 1;
        return 0;
    });
    
    let html = '';
    Object.keys(grouped).forEach(type => {
        html += `<div class="dropdown-header">${typeLabels[type] || type}</div>`;
        grouped[type].forEach(result => {
            html += `
                <a href="${result.url}" class="dropdown-item">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bx ${result.icon} fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${escapeHtml(result.title)}</div>
                            <small class="text-muted">${escapeHtml(result.subtitle)}</small>
                        </div>
                    </div>
                </a>
            `;
        });
    });
    
    resultsContent.innerHTML = html;
    searchResults.style.display = 'block';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.global-search-dropdown {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.global-search-dropdown .dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.global-search-dropdown .dropdown-item:hover {
    background-color: #f8f9fa;
}

.global-search-dropdown .dropdown-item:last-child {
    border-bottom: none;
}

.global-search-dropdown .dropdown-header {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6c757d;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

@media (max-width: 991px) {
    .search-bar {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1050;
        background: white;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .search-bar.d-none {
        display: none !important;
    }
}
</style>
