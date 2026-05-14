<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        if (
            !auth()->user()->hasPermissionTo('view inventory categories')
            && !auth()->user()->hasPermissionTo('manage inventory categories')
        ) {
            abort(403, 'Unauthorized access.');
        }
        
        if ($request->ajax()) {
            $companyId = Auth::user()->company_id;
            $categories = Category::where('company_id', $companyId)
                ->select('inventory_categories.*')
                ->selectRaw('(SELECT COUNT(*) FROM inventory_items WHERE inventory_items.category_id = inventory_categories.id) as items_count');

            return DataTables::of($categories)
                ->addColumn('status_badge', function ($category) {
                    if ($category->is_active) {
                        return '<span class="badge rounded-pill text-success bg-light-success p-2 text-uppercase px-3">
                                <i class="bx bxs-circle me-1"></i>Active
                                </span>';
                    } else {
                        return '<span class="badge rounded-pill text-danger bg-light-danger p-2 text-uppercase px-3">
                                <i class="bx bxs-circle me-1"></i>Inactive
                                </span>';
                    }
                })
                ->addColumn('actions', function ($category) {
                    $actions = '<div class="d-flex gap-1">';
                    
                    // View button (always show)
                    $actions .= '<a href="' . route('inventory.categories.show', $category->encoded_id) . '" class="btn btn-sm btn-outline-info" title="View Items">
                                <i class="bx bx-show"></i>
                                </a>';
                    
                    // Check permissions directly
                    if (auth()->user()->hasPermissionTo('manage inventory categories')) {
                        // Edit button
                        $actions .= '<a href="' . route('inventory.categories.edit', $category->encoded_id) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bx bx-edit"></i>
                                    </a>';
                        
                        // Delete button
                        $actions .= '<form method="POST" action="' . route('inventory.categories.destroy', $category->encoded_id) . '" class="d-inline">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-sm btn-outline-danger delete-category" data-name="' . $category->name . '" title="Delete">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    </form>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('description', function ($category) {
                    return $category->description ?? '<span class="text-muted">N/A</span>';
                })
                ->editColumn('items_count', function ($category) {
                    $count = $category->items_count ?? 0;
                    return '<span class="badge rounded-pill text-success bg-light-success p-2 text-uppercase px-3">
                            <i class="bx bxs-circle me-1"></i>' . $count . '
                            </span>';
                })
                ->editColumn('created_at', function ($category) {
                    return $category->created_at->format('M d, Y');
                })
                ->rawColumns(['status_badge', 'actions', 'description', 'items_count'])
                ->make(true);
        }

        return view('inventory.categories.index');
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('manage inventory categories')) {
            abort(403, 'Unauthorized access.');
        }
        
        return view('inventory.categories.create');
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        $category = Category::findOrFail($id);

        if (
            auth()->user()->company_id !== ($category->company_id ?? null)
            || (
                !auth()->user()->hasPermissionTo('view inventory categories')
                && !auth()->user()->hasPermissionTo('manage inventory categories')
            )
        ) {
            abort(403, 'Unauthorized access.');
        }
        
        $items = $category->items()
            ->where('company_id', auth()->user()->company_id)
            ->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);
        
        return view('inventory.categories.show', compact('category', 'items'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory categories')) {
            abort(403, 'Unauthorized access.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:inventory_categories,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Category::create([
            'company_id' => Auth::user()->company_id,
            'branch_id' => Auth::user()->branch_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        $category = Category::findOrFail($id);

        if (!auth()->user()->hasPermissionTo('manage inventory categories') || 
            auth()->user()->company_id !== ($category->company_id ?? null)) {
            abort(403, 'Unauthorized access.');
        }
        
        return view('inventory.categories.edit', compact('category'));
    }

    public function update(Request $request, string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        $category = Category::findOrFail($id);
        if (!auth()->user()->hasPermissionTo('manage inventory categories') || 
            auth()->user()->company_id !== ($category->company_id ?? null)) {
            abort(403, 'Unauthorized access.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:inventory_categories,code,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        $category = Category::findOrFail($id);

        if (!auth()->user()->hasPermissionTo('manage inventory categories') || 
            auth()->user()->company_id !== ($category->company_id ?? null)) {
            abort(403, 'Unauthorized access.');
        }
        
        // Check if category has any items
        if ($category->items()->exists()) {
            return back()->with('error', 'Cannot delete category with existing items.');
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
