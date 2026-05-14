@extends('layouts.main')

@section('title', 'Consolidated Management Report')

@section('content')
<div class="page-wrapper">
	<div class="page-content">
		<x-breadcrumbs-with-icons :links="[
			['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
			['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
			['label' => 'Consolidated Management Report', 'url' => '#', 'icon' => 'bx bx-bar-chart-alt-2']
		]" />

		<h6 class="mb-0 text-uppercase">CONSOLIDATED MANAGEMENT REPORT</h6>
		<hr />

		<div class="card mb-3">
			<div class="card-body">
				<form method="GET" action="{{ route('accounting.reports.consolidated-management-report') }}" class="row g-3 align-items-end">
					<div class="col-md-3">
						<label class="form-label">Period</label>
						<select name="period" class="form-select" id="cmr-period">
							<option value="month" {{ $period === 'month' ? 'selected' : '' }}>Monthly</option>
							<option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>Quarterly</option>
							<option value="year" {{ $period === 'year' ? 'selected' : '' }}>Yearly</option>
						</select>
					</div>
                    <div class="col-md-3" id="cmr-month-wrap">
						<label class="form-label">Month</label>
						<select name="month" class="form-select">
							@for ($m = 1; $m <= 12; $m++)
								<option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
							@endfor
						</select>
					</div>
                    <div class="col-md-3" id="cmr-quarter-wrap" style="display:none;">
                        <label class="form-label">Quarter</label>
                        <select name="quarter" class="form-select">
                            @for ($q = 1; $q <= 4; $q++)
                                <option value="{{ $q }}" {{ (int)($quarter ?? 0) === $q ? 'selected' : '' }}>Q{{ $q }}</option>
                            @endfor
                        </select>
                    </div>
					<div class="col-md-2">
						<label class="form-label">Year</label>
						<select name="year" class="form-select">
							@for ($y = date('Y'); $y >= date('Y') - 5; $y--)
								<option value="{{ $y }}" {{ (int)$year === $y ? 'selected' : '' }}>{{ $y }}</option>
							@endfor
						</select>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-primary"><i class="bx bx-refresh me-1"></i>Generate</button>
					</div>
				</form>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-body">
				<form method="POST" action="{{ route('accounting.reports.consolidated-management-report.kpis') }}" class="row g-3 align-items-end">
					@csrf
					<input type="hidden" name="period" value="{{ $period }}">
					<input type="hidden" name="year" value="{{ $year }}">
					<input type="hidden" name="month" value="{{ $month }}">
					<div class="col-12">
						<label class="form-label"><strong>KPIs</strong> (toggle to show/hide)</label>
						@php $enabled = \App\Models\SystemSetting::getValue('cmr_enabled_kpis'); $enabled = $enabled ? (is_array($enabled) ? $enabled : json_decode((string)$enabled, true)) : []; @endphp
						
						<!-- Core Financial KPIs -->
						<div class="mb-3">
							<div class="d-flex align-items-center gap-2 mb-2">
								<strong class="text-primary">Core Financial</strong>
								<button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleCategory('core-financial')">Check All</button>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_revenue" name="kpis[]" value="revenue" {{ in_array('revenue', $enabled ?? ['revenue','expenses','net_profit']) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_revenue">Revenue</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_expenses" name="kpis[]" value="expenses" {{ in_array('expenses', $enabled ?? ['revenue','expenses','net_profit']) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_expenses">Expenses</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_net_profit" name="kpis[]" value="net_profit" {{ in_array('net_profit', $enabled ?? ['revenue','expenses','net_profit']) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_net_profit">Net Profit</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_cash_flow" name="kpis[]" value="cash_flow" {{ in_array('cash_flow', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_cash_flow">Cash Flow</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_net_profit_margin" name="kpis[]" value="net_profit_margin" {{ in_array('net_profit_margin', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_net_profit_margin">Net Profit Margin (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_expense_ratio" name="kpis[]" value="expense_ratio" {{ in_array('expense_ratio', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_expense_ratio">Expense Ratio (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_gross_profit_margin" name="kpis[]" value="gross_profit_margin" {{ in_array('gross_profit_margin', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_gross_profit_margin">Gross Profit Margin (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_receivables" name="kpis[]" value="receivables" {{ in_array('receivables', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_receivables">Outstanding Receivables</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_dso" name="kpis[]" value="dso" {{ in_array('dso', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_dso">Debtors Collection Period (Days)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_dpo" name="kpis[]" value="dpo" {{ in_array('dpo', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_dpo">Creditors Payment Period (Days)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input core-financial" type="checkbox" id="kpi_dio" name="kpis[]" value="dio" {{ in_array('dio', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_dio">Inventory Holding Period (Days)</label>
							</div>
						</div>

						<!-- 🔹 1. Liquidity & Solvency KPIs -->
						<div class="mb-3">
							<div class="d-flex align-items-center gap-2 mb-2">
								<strong class="text-info">Liquidity & Solvency</strong>
								<button type="button" class="btn btn-sm btn-outline-info" onclick="toggleCategory('liquidity')">Check All</button>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input liquidity" type="checkbox" id="kpi_current_ratio" name="kpis[]" value="current_ratio" {{ in_array('current_ratio', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_current_ratio">Current Ratio</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input liquidity" type="checkbox" id="kpi_quick_ratio" name="kpis[]" value="quick_ratio" {{ in_array('quick_ratio', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_quick_ratio">Quick Ratio (Acid Test)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input liquidity" type="checkbox" id="kpi_cash_ratio" name="kpis[]" value="cash_ratio" {{ in_array('cash_ratio', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_cash_ratio">Cash Ratio</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input liquidity" type="checkbox" id="kpi_debt_to_equity" name="kpis[]" value="debt_to_equity" {{ in_array('debt_to_equity', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_debt_to_equity">Debt-to-Equity Ratio</label>
							</div>
						</div>

						<!-- 🔹 2. Efficiency / Activity KPIs -->
						<div class="mb-3">
							<div class="d-flex align-items-center gap-2 mb-2">
								<strong class="text-success">Efficiency / Activity</strong>
								<button type="button" class="btn btn-sm btn-outline-success" onclick="toggleCategory('efficiency')">Check All</button>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input efficiency" type="checkbox" id="kpi_asset_turnover" name="kpis[]" value="asset_turnover" {{ in_array('asset_turnover', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_asset_turnover">Asset Turnover Ratio</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input efficiency" type="checkbox" id="kpi_inventory_turnover" name="kpis[]" value="inventory_turnover" {{ in_array('inventory_turnover', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_inventory_turnover">Inventory Turnover Ratio</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input efficiency" type="checkbox" id="kpi_receivables_turnover" name="kpis[]" value="receivables_turnover" {{ in_array('receivables_turnover', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_receivables_turnover">Receivables Turnover Ratio</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input efficiency" type="checkbox" id="kpi_payables_turnover" name="kpis[]" value="payables_turnover" {{ in_array('payables_turnover', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_payables_turnover">Payables Turnover Ratio</label>
							</div>
						</div>

						<!-- 🔹 3. Profitability & Return KPIs -->
						<div class="mb-3">
							<div class="d-flex align-items-center gap-2 mb-2">
								<strong class="text-warning">Profitability & Return</strong>
								<button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleCategory('profitability')">Check All</button>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input profitability" type="checkbox" id="kpi_roa" name="kpis[]" value="roa" {{ in_array('roa', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_roa">Return on Assets (ROA) (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input profitability" type="checkbox" id="kpi_roe" name="kpis[]" value="roe" {{ in_array('roe', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_roe">Return on Equity (ROE) (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input profitability" type="checkbox" id="kpi_operating_profit_margin" name="kpis[]" value="operating_profit_margin" {{ in_array('operating_profit_margin', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_operating_profit_margin">Operating Profit Margin (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input profitability" type="checkbox" id="kpi_ebitda_margin" name="kpis[]" value="ebitda_margin" {{ in_array('ebitda_margin', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_ebitda_margin">EBITDA Margin (%)</label>
							</div>
						</div>

						<!-- 🔹 4. Growth KPIs -->
						<div class="mb-3">
							<div class="d-flex align-items-center gap-2 mb-2">
								<strong class="text-danger">Growth</strong>
								<button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleCategory('growth')">Check All</button>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input growth" type="checkbox" id="kpi_revenue_growth_rate" name="kpis[]" value="revenue_growth_rate" {{ in_array('revenue_growth_rate', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_revenue_growth_rate">Revenue Growth Rate (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input growth" type="checkbox" id="kpi_net_profit_growth_rate" name="kpis[]" value="net_profit_growth_rate" {{ in_array('net_profit_growth_rate', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_net_profit_growth_rate">Net Profit Growth Rate (%)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input growth" type="checkbox" id="kpi_expense_growth_rate" name="kpis[]" value="expense_growth_rate" {{ in_array('expense_growth_rate', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_expense_growth_rate">Expense Growth Rate (%)</label>
							</div>
						</div>

						<!-- 🔹 5. Cash Flow Health KPIs -->
						<div class="mb-3">
							<div class="d-flex align-items-center gap-2 mb-2">
								<strong class="text-secondary">Cash Flow Health</strong>
								<button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCategory('cash-flow')">Check All</button>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input cash-flow" type="checkbox" id="kpi_operating_cash_flow_ratio" name="kpis[]" value="operating_cash_flow_ratio" {{ in_array('operating_cash_flow_ratio', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_operating_cash_flow_ratio">Operating Cash Flow Ratio</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input cash-flow" type="checkbox" id="kpi_free_cash_flow" name="kpis[]" value="free_cash_flow" {{ in_array('free_cash_flow', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_free_cash_flow">Free Cash Flow (FCF)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input cash-flow" type="checkbox" id="kpi_cash_conversion_cycle" name="kpis[]" value="cash_conversion_cycle" {{ in_array('cash_conversion_cycle', $enabled ?? []) ? 'checked' : '' }}>
								<label class="form-check-label" for="kpi_cash_conversion_cycle">Cash Conversion Cycle (Days)</label>
							</div>
						</div>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-secondary"><i class="bx bx-save me-1"></i>Save KPIs</button>
					</div>
				</form>
			</div>
		</div>

		<div class="card border-0 shadow-sm">
			<div class="card-body">
				<div class="d-flex justify-content-between align-items-center">
					<h5 class="mb-3">Executive Summary</h5>
					<form method="GET" action="{{ route('accounting.reports.consolidated-management-report') }}">
						<input type="hidden" name="period" value="{{ $period }}">
						<input type="hidden" name="year" value="{{ $year }}">
						<input type="hidden" name="month" value="{{ $month }}">
					<button type="submit" formaction="{{ route('accounting.reports.consolidated-management-report.export') }}" class="btn btn-danger">
						<i class="bx bxs-file-pdf me-1"></i> Generate PDF
					</button>
					<button type="submit" formaction="{{ route('accounting.reports.consolidated-management-report.export-word') }}" class="btn btn-success">
						<i class="bx bxs-file-doc me-1"></i> Export Word
					</button>
					</form>
				</div>
				<p class="text-muted" style="text-align: justify;">{{ $summary ?? '—' }}</p>
				<hr>
				<h5 class="mb-3">Key Performance Indicators</h5>
				<div class="row row-cols-1 row-cols-md-3 g-3">
					@foreach(($kpis ?? []) as $kpi)
						<div class="col">
							<div class="card h-100">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
                                        <p class="mb-1 text-muted">{{ $kpi['label'] }}</p>
                                        @php 
                                            $key = $kpi['key'] ?? '';
                                            $isPercent = in_array($key, ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate']); 
                                            $isDays = in_array($key, ['dso','dpo','dio','cash_conversion_cycle']); 
                                            $isRatio = in_array($key, ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio']);
                                        @endphp
                                        <h4 class="mb-0">
                                            @if($isPercent)
                                                {{ number_format((float)($kpi['value'] ?? 0), 1) }}%
                                            @elseif($isDays)
                                                {{ number_format((float)($kpi['value'] ?? 0), 0) }} days
                                            @elseif($isRatio)
                                                {{ number_format((float)($kpi['value'] ?? 0), 2) }}
                                            @else
                                                TZS {{ number_format((float)($kpi['value'] ?? 0), 2) }}
                                            @endif
                                        </h4>
                                        @if($kpi['previous'] !== null)
                                        <small class="text-muted">
                                            Prev:
                                            @if($isPercent)
                                                {{ number_format((float)($kpi['previous'] ?? 0), 1) }}%
                                            @elseif($isDays)
                                                {{ number_format((float)($kpi['previous'] ?? 0), 0) }} days
                                            @elseif($isRatio)
                                                {{ number_format((float)($kpi['previous'] ?? 0), 2) }}
                                            @else
                                                TZS {{ number_format((float)($kpi['previous'] ?? 0), 2) }}
                                            @endif
                                        </small>
                                        @endif
											<div>
												@php $chg = (float)($kpi['change_percent'] ?? 0); $up = ($kpi['trend'] ?? '') === 'up'; $down = ($kpi['trend'] ?? '') === 'down'; @endphp
												<small class="{{ $up ? 'text-success' : ($down ? 'text-danger' : 'text-secondary') }}">
													<i class='bx {{ $up ? 'bx-up-arrow-alt' : ($down ? 'bx-down-arrow-alt' : 'bx-minus') }}'></i>
													{{ number_format($chg, 1) }}%
												</small>
											</div>
										</div>
                                    <i class="bx {{ ($kpi['trend'] ?? '') === 'down' ? 'bx-trending-down text-danger' : 'bx-trending-up text-success' }}"></i>
									</div>
								</div>
							</div>
						</div>
					@endforeach
				</div>
				<hr>
				<h5 class="mb-3">Charts & Trends</h5>
				<canvas id="cmrChart" height="120"></canvas>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    const period = '{{ $period }}';
    const monthWrap = document.getElementById('cmr-month-wrap');
    const periodSel = document.getElementById('cmr-period');
    const quarterWrap = document.getElementById('cmr-quarter-wrap');
    function toggleMonth(){
        if (!periodSel) return;
        const isMonth = periodSel.value === 'month';
        const isQuarter = periodSel.value === 'quarter';
        if (monthWrap) monthWrap.style.display = isMonth ? '' : 'none';
        if (quarterWrap) quarterWrap.style.display = isQuarter ? '' : 'none';
    }
    toggleMonth();
    periodSel && periodSel.addEventListener('change', toggleMonth);

    // Toggle all checkboxes in a category
    window.toggleCategory = function(category) {
        const checkboxes = document.querySelectorAll('input.' + category);
        if (checkboxes.length === 0) return;
        
        // Check if all are checked
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        // Toggle all checkboxes
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
    };

    const kpis = @json($kpis ?? []);
    const labels = kpis.map(k => k.label);
    const values = kpis.map(k => Number(k.value || 0));
    const colors = kpis.map(k => (k.trend === 'down' ? '#e74c3c' : (k.key === 'expenses' ? '#e74c3c' : '#2ecc71')));

    const ctx = document.getElementById('cmrChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Value',
                    data: values,
                    backgroundColor: colors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context){
                                const raw = context.parsed;
                                const val = (raw && typeof raw === 'object') ? Number(raw.y || 0) : Number(raw || 0);
                                const key = kpis[context.dataIndex]?.key;
                                const isPercent = ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate'].includes(key);
                                const isDays = ['dso','dpo','dio','cash_conversion_cycle'].includes(key);
                                const isRatio = ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio'].includes(key);
                                if (isPercent) return val.toFixed(1) + '%';
                                if (isDays) return Math.round(val) + ' days';
                                if (isRatio) return val.toFixed(2);
                                return 'TZS ' + val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
})();
</script>
@endpush
