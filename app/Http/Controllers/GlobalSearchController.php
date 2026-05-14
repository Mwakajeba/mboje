<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory\Item;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\PosSale;
use App\Models\Sales\CashSale;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    /**
     * Perform global search across multiple models
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'results' => [],
                    'total' => 0
                ]);
            }

            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'results' => [],
                    'total' => 0
                ], 401);
            }
            
            $companyId = $user->company_id;
            $branchId = $user->branch_id;
            
            if (!$companyId) {
                return response()->json([
                    'results' => [],
                    'total' => 0,
                    'error' => 'User company not found'
                ], 400);
            }
            
            $results = [];
            $limit = 5; // Limit per category
            
            // Search for pages/links based on query
            $pageSuggestions = $this->getPageSuggestions($query);
            foreach ($pageSuggestions as $page) {
                $results[] = $page;
            }

        // Search Customers
        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('customerNo', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($customers as $customer) {
            $results[] = [
                'type' => 'customer',
                'title' => $customer->name,
                'subtitle' => 'Customer #' . $customer->customerNo . ($customer->phone ? ' • ' . $customer->phone : ''),
                'url' => route('customers.show', $customer->encoded_id),
                'icon' => 'bx-user'
            ];
        }

        // Search Sales Invoices
        $salesInvoices = SalesInvoice::with('customer')
            ->whereHas('customer', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('invoice_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', function($customerQ) use ($query) {
                      $customerQ->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();

        foreach ($salesInvoices as $invoice) {
            $results[] = [
                'type' => 'sales_invoice',
                'title' => $invoice->invoice_number,
                'subtitle' => ($invoice->customer ? $invoice->customer->name : 'Unknown') . ' • ' . number_format($invoice->total_amount, 2),
                'url' => route('sales.invoices.show', $invoice->encoded_id),
                'icon' => 'bx-receipt'
            ];
        }

        // Search Sales Orders
        $salesOrders = SalesOrder::with('customer')
            ->whereHas('customer', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('order_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', function($customerQ) use ($query) {
                      $customerQ->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();

        foreach ($salesOrders as $order) {
            $orderId = \Vinkla\Hashids\Facades\Hashids::encode($order->id);
            $results[] = [
                'type' => 'sales_order',
                'title' => $order->order_number,
                'subtitle' => ($order->customer ? $order->customer->name : 'Unknown') . ' • ' . number_format($order->total_amount, 2),
                'url' => route('sales.orders.show', $orderId),
                'icon' => 'bx-cart'
            ];
        }

        // Search POS Sales
        $posSales = PosSale::with('customer')
            ->where('company_id', $companyId)
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('pos_number', 'like', "%{$query}%")
                  ->orWhere('customer_name', 'like', "%{$query}%")
                  ->orWhereHas('customer', function($customerQ) use ($query) {
                      $customerQ->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();

        foreach ($posSales as $posSale) {
            $results[] = [
                'type' => 'pos_sale',
                'title' => $posSale->pos_number,
                'subtitle' => ($posSale->customer_name ?: ($posSale->customer ? $posSale->customer->name : 'Walk-in')) . ' • ' . number_format($posSale->total_amount, 2),
                'url' => route('sales.pos.show', $posSale->encoded_id),
                'icon' => 'bx-shopping-bag'
            ];
        }

        // Search Cash Sales
        $cashSales = CashSale::with('customer')
            ->where('company_id', $companyId)
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('sale_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', function($customerQ) use ($query) {
                      $customerQ->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();

        foreach ($cashSales as $cashSale) {
            $customerName = $cashSale->customer ? $cashSale->customer->name : 'Walk-in';
            $results[] = [
                'type' => 'cash_sale',
                'title' => $cashSale->sale_number,
                'subtitle' => $customerName . ' • ' . number_format($cashSale->total_amount, 2),
                'url' => route('sales.cash-sales.show', $cashSale->encoded_id),
                'icon' => 'bx-money'
            ];
        }

        // Search Inventory Items
        $items = Item::where('company_id', $companyId)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            $subtitleParts = [];
            if ($item->code) {
                $subtitleParts[] = 'Code: ' . $item->code;
            }
            if ($item->barcode) {
                $subtitleParts[] = 'Barcode: ' . $item->barcode;
            }
            if ($item->sku) {
                $subtitleParts[] = 'SKU: ' . $item->sku;
            }
            
            $results[] = [
                'type' => 'item',
                'title' => $item->name,
                'subtitle' => implode(' • ', $subtitleParts) ?: 'Inventory Item',
                'url' => route('inventory.items.show', $item->hash_id),
                'icon' => 'bx-package'
            ];
        }

        // Search Suppliers
        $suppliers = Supplier::where('company_id', $companyId)
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($suppliers as $supplier) {
            // Use Hashids to encode the supplier ID
            $supplierId = \Vinkla\Hashids\Facades\Hashids::encode($supplier->id);
            $results[] = [
                'type' => 'supplier',
                'title' => $supplier->name,
                'subtitle' => ($supplier->phone ? $supplier->phone : '') . ($supplier->email ? ' • ' . $supplier->email : ''),
                'url' => route('accounting.suppliers.show', $supplierId),
                'icon' => 'bx-store'
            ];
        }

        // Search Purchase Invoices
        $purchaseInvoices = PurchaseInvoice::with('supplier')
            ->whereHas('supplier', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('invoice_number', 'like', "%{$query}%")
                  ->orWhereHas('supplier', function($supplierQ) use ($query) {
                      $supplierQ->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();

        foreach ($purchaseInvoices as $invoice) {
            $results[] = [
                'type' => 'purchase_invoice',
                'title' => $invoice->invoice_number,
                'subtitle' => ($invoice->supplier ? $invoice->supplier->name : 'Unknown') . ' • ' . number_format($invoice->total_amount, 2),
                'url' => route('purchases.purchase-invoices.show', $invoice->encoded_id),
                'icon' => 'bx-purchase-tag'
            ];
        }

        // Search Receipt Vouchers
        $receipts = Receipt::with('receiptItems')
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('reference', 'like', "%{$query}%")
                  ->orWhere('reference_number', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($receipts as $receipt) {
            $totalAmount = $receipt->receiptItems->sum('amount') ?? $receipt->amount ?? 0;
            $results[] = [
                'type' => 'receipt',
                'title' => $receipt->reference,
                'subtitle' => 'Amount: ' . number_format($totalAmount, 2) . ' • ' . ($receipt->reference_type == 'sales_invoice' ? 'Invoice Payment' : 'Receipt Voucher'),
                'url' => route('accounting.receipt-vouchers.show', $receipt->encoded_id),
                'icon' => 'bx-money-withdraw'
            ];
        }

        // Search Payment Vouchers
        $paymentVouchers = Payment::whereHas('bankAccount.chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('reference', 'like', "%{$query}%")
                  ->orWhere('reference_number', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($paymentVouchers as $pv) {
            $results[] = [
                'type' => 'payment_voucher',
                'title' => $pv->reference_number ?: $pv->reference,
                'subtitle' => 'Amount: ' . number_format($pv->total_amount, 2),
                'url' => route('accounting.payment-vouchers.show', $pv->hash_id),
                'icon' => 'bx-credit-card'
            ];
        }

        // Search Bills
        $bills = Bill::where('company_id', $companyId)
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->where(function($q) use ($query) {
                $q->where('reference', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($bills as $bill) {
            $results[] = [
                'type' => 'bill',
                'title' => $bill->reference,
                'subtitle' => 'Amount: ' . number_format($bill->total_amount, 2),
                'url' => route('accounting.bill-purchases.show', $bill),
                'icon' => 'bx-file'
            ];
        }

        // Search Menus (only menus accessible to the user)
        $user = Auth::user();
        if ($user && $user->roles->isNotEmpty()) {
            $roleIds = $user->roles->pluck('id')->toArray();
            $addedMenuIds = []; // Track added menu IDs to prevent duplicates
            
            // Search all menus (both parent and child) that match the query
            $allMenus = Menu::with(['parent', 'children'])
                ->whereHas('roles', function ($q) use ($roleIds) {
                    $q->whereIn('roles.id', $roleIds);
                })
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->limit($limit * 2) // Get more to account for filtering
                ->get();

            foreach ($allMenus as $menu) {
                // Skip if already added
                if (in_array($menu->id, $addedMenuIds)) {
                    continue;
                }

                // Skip edit/delete/create routes
                $isEditOrDelete = $menu->route && (
                    strpos($menu->route, 'edit') !== false || 
                    strpos($menu->route, 'delete') !== false || 
                    strpos($menu->route, 'destroy') !== false || 
                    strpos($menu->route, 'create') !== false
                );

                if ($isEditOrDelete || !$menu->route) {
                    continue;
                }

                // Handle special route resolution
                $resolvedRoute = $menu->route === 'reports.purchases' 
                    ? 'purchases.reports.index' 
                    : $menu->route;
                
                if (!\Route::has($resolvedRoute)) {
                    continue;
                }

                // Determine subtitle based on whether it's a parent or child menu
                if ($menu->parent_id) {
                    // Child menu
                    $parentName = $menu->parent ? $menu->parent->name : 'Menu';
                    $subtitle = $parentName . ' → ' . $menu->name;
                    $icon = $menu->icon ?? ($menu->parent ? $menu->parent->icon : null) ?? 'bx-menu';
                } else {
                    // Parent menu
                    $subtitle = 'Menu';
                    $icon = $menu->icon ?? 'bx-menu';
                }

                $results[] = [
                    'type' => 'menu',
                    'title' => $menu->name,
                    'subtitle' => $subtitle,
                    'url' => route($resolvedRoute),
                    'icon' => $icon
                ];

                // Mark as added
                $addedMenuIds[] = $menu->id;
            }
        }

        return response()->json([
            'results' => $results,
            'total' => count($results)
        ]);
        } catch (\Exception $e) {
            \Log::error('Global search error: ' . $e->getMessage(), [
                'query' => $request->get('q'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'results' => [],
                'total' => 0,
                'error' => 'An error occurred while searching',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while searching'
            ], 500);
        }
    }
    
    /**
     * Get page suggestions based on search query
     */
    private function getPageSuggestions($query)
    {
        $queryLower = strtolower($query);
        $suggestions = [];
        
        // Define pages with their search keywords
        $pages = [
            [
                'keywords' => ['dashboard', 'home', 'main'],
                'title' => 'Dashboard',
                'url' => route('dashboard'),
                'icon' => 'bx-home',
                'description' => 'View dashboard'
            ],
            [
                'keywords' => ['sales invoice', 'sales invoices', 'invoice', 'invoices'],
                'title' => 'Sales Invoices',
                'url' => route('sales.invoices.index'),
                'icon' => 'bx-receipt',
                'description' => 'View all sales invoices'
            ],
            [
                'keywords' => ['sales order', 'sales orders', 'order', 'orders'],
                'title' => 'Sales Orders',
                'url' => route('sales.orders.index'),
                'icon' => 'bx-cart',
                'description' => 'View all sales orders'
            ],
            [
                'keywords' => ['pos', 'pos sale', 'pos sales', 'point of sale'],
                'title' => 'POS Sales',
                'url' => route('sales.pos.index'),
                'icon' => 'bx-shopping-bag',
                'description' => 'Point of sale'
            ],
            [
                'keywords' => ['cash sale', 'cash sales'],
                'title' => 'Cash Sales',
                'url' => route('sales.cash-sales.index'),
                'icon' => 'bx-money',
                'description' => 'View all cash sales'
            ],
            [
                'keywords' => ['purchase invoice', 'purchase invoices', 'purchase'],
                'title' => 'Purchase Invoices',
                'url' => route('purchases.purchase-invoices.index'),
                'icon' => 'bx-purchase-tag',
                'description' => 'View all purchase invoices'
            ],
            [
                'keywords' => ['customer', 'customers'],
                'title' => 'Customers',
                'url' => route('customers.index'),
                'icon' => 'bx-user',
                'description' => 'View all customers'
            ],
            [
                'keywords' => ['supplier', 'suppliers'],
                'title' => 'Suppliers',
                'url' => route('accounting.suppliers.index'),
                'icon' => 'bx-store',
                'description' => 'View all suppliers'
            ],
            [
                'keywords' => ['inventory', 'item', 'items', 'product', 'products'],
                'title' => 'Inventory Items',
                'url' => route('inventory.items.index'),
                'icon' => 'bx-package',
                'description' => 'View all inventory items'
            ],
            [
                'keywords' => ['receipt', 'receipts', 'receipt voucher'],
                'title' => 'Receipt Vouchers',
                'url' => route('accounting.receipt-vouchers.index'),
                'icon' => 'bx-money-withdraw',
                'description' => 'View all receipt vouchers'
            ],
            [
                'keywords' => ['payment voucher', 'payment vouchers', 'payment'],
                'title' => 'Payment Vouchers',
                'url' => route('accounting.payment-vouchers.index'),
                'icon' => 'bx-credit-card',
                'description' => 'View all payment vouchers'
            ],
            [
                'keywords' => ['bill', 'bills', 'bill purchase'],
                'title' => 'Bill Purchases',
                'url' => route('accounting.bill-purchases'),
                'icon' => 'bx-file',
                'description' => 'View all bill purchases'
            ],
            [
                'keywords' => ['accounting', 'accounts', 'chart of accounts'],
                'title' => 'Chart of Accounts',
                'url' => route('accounting.chart-accounts.index'),
                'icon' => 'bx-calculator',
                'description' => 'View chart of accounts'
            ],
            [
                'keywords' => ['journal', 'journals', 'journal entry'],
                'title' => 'Journal Entries',
                'url' => route('accounting.journals.index'),
                'icon' => 'bx-book',
                'description' => 'View all journal entries'
            ],
            [
                'keywords' => ['bank reconciliation', 'bank', 'reconciliation'],
                'title' => 'Bank Reconciliation',
                'url' => route('accounting.bank-reconciliation.index'),
                'icon' => 'bx-credit-card-front',
                'description' => 'View bank reconciliations'
            ],
        ];
        
        // Check if query matches any page keywords
        foreach ($pages as $page) {
            foreach ($page['keywords'] as $keyword) {
                if (stripos($queryLower, $keyword) !== false || stripos($keyword, $queryLower) !== false) {
                    $suggestions[] = [
                        'type' => 'page',
                        'title' => $page['title'],
                        'subtitle' => $page['description'],
                        'url' => $page['url'],
                        'icon' => $page['icon']
                    ];
                    break; // Only add once per page
                }
            }
        }
        
        return $suggestions;
    }
}

