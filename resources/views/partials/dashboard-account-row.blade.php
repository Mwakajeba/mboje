@php 
    $hasChildren = !empty($account['children']);
    $accountId = 'acc-' . $account['account_id'];
    
    // Get previous year data for this account
    $prevYearAccount = null;
    $findAccount = function($items, $id) use (&$findAccount) {
        foreach($items as $item) {
            if($item['account_id'] == $id) return $item;
            if(!empty($item['children'])) {
                $found = $findAccount($item['children'], $id);
                if($found) return $found;
            }
        }
        return null;
    };

    $prevYearFsli = $previousYearData[$mainGroupName]['fslis'][$fsliName] ?? [];
    $prevYearAccount = $findAccount($prevYearFsli['accounts'] ?? [], $account['account_id']);
    $prevYearAmount = $prevYearAccount['sum'] ?? 0;
    $change = $account['sum'] - $prevYearAmount;
    
    // Indentation based on depth
    $paddingLeft = 30 + ($depth * 20);
@endphp

@if($account['sum'] != 0 || $prevYearAmount != 0 || $hasChildren)
<tr class="account-row collapse {{ $fsliId }} {{ $hasChildren ? 'parent-account clickable' : '' }}" 
    @if($hasChildren) data-bs-toggle="collapse" data-bs-target=".{{ $accountId }}" aria-expanded="false" @endif>
    <td style="padding-left: {{ $paddingLeft }}px;">
        @if($hasChildren)
            <i class="bx bx-chevron-right me-1 transition-icon"></i>
        @else
            <i class="bx bx-link-external me-1 text-muted small"></i>
        @endif
        
        <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($account['account_id'])) }}"
            class="text-decoration-none text-dark {{ $hasChildren ? 'fw-bold' : 'fw-medium' }}">
            @if($account['account_code'])<span class="text-muted small">{{ $account['account_code'] }} - </span>@endif
            {{ $account['account'] }}
        </a>
    </td>
    <td class="text-end">
        <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($account['account_id'])) }}"
            class="text-decoration-none fw-bold text-dark">
            {{ number_format($account['sum'], 2) }}
        </a>
    </td>
    <td class="text-end text-dark">
        {{ number_format($prevYearAmount, 2) }}
    </td>
    <td class="text-end">
        {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 2) }}
    </td>
</tr>

@if($hasChildren)
    @foreach($account['children'] as $child)
        @include('partials.dashboard-account-row', [
            'account' => $child,
            'mainGroupName' => $mainGroupName,
            'fsliName' => $fsliName,
            'fsliId' => $accountId,
            'previousYearData' => $previousYearData,
            'depth' => $depth + 1
        ])
    @endforeach
@endif
@endif