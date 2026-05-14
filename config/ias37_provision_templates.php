<?php

return [
    'legal_claim' => [
        'label' => 'Legal Claim / Lawsuit',
        'description' => 'Use when the entity is sued and legal advisors confirm a probable loss with a reliable estimate.',
        'examples' => [
            'Customer lawsuit for breach of contract',
            'Regulatory fine when outcome is probable',
        ],
        'gl_pattern' => 'Dr Legal Expense / Cr Provision – Legal Claims',
        'notes' => 'Settlement: Dr Provision – Legal Claims / Cr Bank or Payables. If case is won, reverse: Dr Provision – Legal Claims / Cr Legal Expense.',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => false, // Usually short-term
            'asset_linkage_fields' => false,
            'probability_fields' => true, // Mandatory for legal claims
            'computation_panel' => true, // Expected value or most likely outcome
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => true,
            'method' => 'expected_value', // or 'most_likely_outcome'
            'formula' => 'Σ (Probability × Outcome)',
            'inputs' => [
                'outcomes' => 'array', // Multiple outcomes with probabilities
                'most_likely_amount' => 'decimal', // Alternative: single best estimate
            ],
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => ['legal_expense'], // Account type/category filter
            'provision_accounts' => ['legal_provision'],
            'unwinding_accounts' => null, // Not applicable
        ],
    ],
    
    'warranty' => [
        'label' => 'Warranty Provision',
        'description' => 'Use for product warranties where historical defect rates support estimating future warranty costs.',
        'examples' => [
            '12-month product warranty on electronics',
            'Service warranty obligations on installed equipment',
        ],
        'gl_pattern' => 'Initial: Dr Warranty Expense / Cr Warranty Provision. Actual repairs: Dr Warranty Provision / Cr Inventory or Cash/Payables.',
        'notes' => 'Typical calculation: Warranty Provision = Sales × Historical defect %. Full automation against sales can be added later using this template key.',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => false, // Usually short-term
            'asset_linkage_fields' => false,
            'probability_fields' => false, // Historical data used instead
            'computation_panel' => true, // Units × Defect % × Cost
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => true,
            'method' => 'expected_value',
            'formula' => 'Units Sold × Defect Rate % × Average Repair Cost',
            'inputs' => [
                'units_sold' => 'integer',
                'defect_rate_percent' => 'decimal',
                'average_repair_cost' => 'decimal',
            ],
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => ['warranty_expense'],
            'provision_accounts' => ['warranty_provision'],
            'unwinding_accounts' => null,
        ],
    ],
    
    'onerous_contract' => [
        'label' => 'Onerous Contract Provision',
        'description' => 'Use when unavoidable costs of a contract exceed expected benefits (onerous contract).',
        'examples' => [
            'Loss-making supply contract that cannot be cancelled without penalty',
        ],
        'gl_pattern' => 'Dr Onerous Contract Expense / Cr Provision – Onerous Contracts',
        'notes' => 'Measurement: lower of (cost to fulfil) and (penalty to exit).',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => true, // Conditional: only for long-term contracts
            'asset_linkage_fields' => false,
            'probability_fields' => false,
            'computation_panel' => true, // Cost to fulfill vs penalty
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => true,
            'method' => 'lower_of',
            'formula' => 'MIN(Cost to Fulfill, Penalty to Exit)',
            'inputs' => [
                'cost_to_fulfill' => 'decimal',
                'penalty_to_exit' => 'decimal',
            ],
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => ['onerous_expense'],
            'provision_accounts' => ['onerous_provision'],
            'unwinding_accounts' => ['finance_cost'], // Only if discounted
        ],
    ],
    
    'environmental' => [
        'label' => 'Environmental Restoration / Decommissioning',
        'description' => 'Use when there is a legal obligation to restore the environment or decommission an asset (e.g. mining, oil, infrastructure).',
        'examples' => [
            'Mine site restoration',
            'Oil platform decommissioning',
        ],
        'gl_pattern' => 'Initial: Dr Asset (PPE) / Cr Provision – Restoration. Over time: Dr Depreciation Expense; Dr Finance Cost / Cr Provision for unwinding.',
        'notes' => 'Select a PPE account as the "expense" line to capitalise the restoration cost. Mark as discounted when the time value of money is material.',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => true, // Always required for environmental
            'asset_linkage_fields' => true, // Mandatory: asset-related obligation
            'probability_fields' => false, // Obligation usually certain
            'computation_panel' => true, // PV calculator
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => true,
            'method' => 'present_value',
            'formula' => 'Future Cost / (1 + r)^n',
            'inputs' => [
                'future_cost' => 'decimal',
                'settlement_year' => 'integer',
                'discount_rate_percent' => 'decimal',
                'inflation_assumption' => 'decimal', // Optional
            ],
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => ['environmental_expense', 'ppe'], // Can be PPE for capitalisation
            'provision_accounts' => ['environmental_provision'],
            'unwinding_accounts' => ['finance_cost'], // Required for discounted provisions
        ],
        
        // Asset linkage rules
        'asset_linkage' => [
            'required' => true,
            'capitalisation_required' => true, // Must capitalise into PPE
        ],
    ],
    
    'restructuring' => [
        'label' => 'Restructuring Provision',
        'description' => 'Use for restructuring plans that meet strict IAS 37 criteria (detailed formal plan and valid expectation).',
        'examples' => [
            'Closure of a business line with termination benefits and unavoidable contract penalties',
        ],
        'gl_pattern' => 'Dr Restructuring Expense / Cr Provision – Restructuring',
        'notes' => 'Allowed: termination benefits, contract termination penalties, direct restructuring costs. Not allowed: training, marketing, future operating losses.',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => false,
            'asset_linkage_fields' => false,
            'probability_fields' => false,
            'computation_panel' => true, // Partial computation: Employees × cost
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => true,
            'method' => 'partial',
            'formula' => 'Employees Affected × Average Termination Cost',
            'inputs' => [
                'employees_affected' => 'integer',
                'average_termination_cost' => 'decimal',
                'contract_termination_penalties' => 'decimal', // Additional
            ],
            'excluded_costs' => [
                'training_costs',
                'marketing_costs',
                'future_operating_losses',
            ],
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => ['restructuring_expense'],
            'provision_accounts' => ['restructuring_provision'],
            'unwinding_accounts' => null,
        ],
    ],
    
    'employee_benefit' => [
        'label' => 'Employee Benefit Provision (IAS 37 scope)',
        'description' => 'Use only for employee obligations not covered by IAS 19 (e.g. special voluntary termination program).',
        'examples' => [
            'One-off voluntary termination scheme outside normal post-employment benefit plans',
        ],
        'gl_pattern' => 'Dr Staff Cost / Cr Provision – Employee Benefits',
        'notes' => 'For regular defined benefit / post-employment obligations, use the IAS 19 / HR modules instead.',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => false,
            'asset_linkage_fields' => false,
            'probability_fields' => false,
            'computation_panel' => false, // Judgment-based
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => false,
            'method' => 'best_estimate',
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => ['employee_benefit_expense'],
            'provision_accounts' => ['employee_benefit_provision'],
            'unwinding_accounts' => null,
        ],
    ],
    
    'other' => [
        'label' => 'Other Specific Provision',
        'description' => 'Use only when a specific IAS 37 obligation does not fit the predefined templates but still meets the three recognition criteria.',
        'examples' => [
            'Site restoration not already covered above',
            'Other legally enforceable obligations with uncertain timing/amount',
        ],
        'gl_pattern' => 'Dr Relevant Expense / Cr Provision – Other',
        'notes' => 'Avoid general "future loss" provisions. Document the specific obligation and assumptions clearly.',
        
        // Field visibility rules
        'field_visibility' => [
            'discounting_fields' => true, // Conditional: if long-term
            'asset_linkage_fields' => false,
            'probability_fields' => true, // Usually required
            'computation_panel' => false, // Judgment-based
        ],
        
        // Computation logic
        'computation' => [
            'enabled' => false,
            'method' => 'best_estimate',
        ],
        
        // Account restrictions
        'account_restrictions' => [
            'expense_accounts' => null, // Flexible
            'provision_accounts' => ['other_provision'],
            'unwinding_accounts' => ['finance_cost'], // Only if discounted
        ],
    ],
];
