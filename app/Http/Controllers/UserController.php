<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Rules\PasswordValidation;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct()
    {
        // Middleware is applied in routes/web.php
    }

    public function index(Request $request)
    {
        // Get all users for stats and dashboard cards (excluding super-admin)
        // Load relationships needed for display
        $users = User::where('company_id', current_company_id())
            ->with(['roles', 'branch'])
            ->whereDoesntHave('roles', function($q) {
                $q->where('name', 'super-admin');
            })
            ->get();
        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->where('status', 'inactive')->count();

        return view('users.index', compact('users', 'totalUsers', 'activeUsers', 'inactiveUsers'));
    }

    public function data(Request $request)
    {
        $query = User::with(['branch', 'roles'])
            ->where('company_id', current_company_id())
            ->whereDoesntHave('roles', function($q) {
                $q->where('name', 'super-admin');
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        return DataTables::of($query)
            ->addColumn('name_display', function($user) {
                $initial = strtoupper(substr($user->name, 0, 1));
                $avatar = '<div class="avatar avatar-sm bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center shadow" style="width:36px; height:36px;">'
                    . '<span class="avatar-title text-white fw-bold" style="font-size:1.25rem;">' . $initial . '</span>'
                    . '</div>';
                $canView = auth()->user()->can('view user profile');
                $nameLink = $canView
                    ? '<div class="fw-bold"><a href="' . route('users.show', $user) . '" class="text-decoration-none">' . e($user->name) . '</a></div>'
                    : '<div class="fw-bold">' . e($user->name) . '</div>';
                return '<div class="d-flex align-items-center">' . $avatar . $nameLink . '</div>';
            })
            ->addColumn('roles_badges', function($user) {
                $badges = '';
                foreach ($user->roles as $role) {
                    $badges .= '<span class="badge bg-primary me-1">' . e($role->name) . '</span>';
                }
                return $badges ?: '-';
            })
            ->editColumn('status', function($user) {
                if ($user->status === 'active') {
                    return '<span class="badge bg-success">' . __('app.active') . '</span>';
                } elseif ($user->status === 'inactive') {
                    return '<span class="badge bg-warning">' . __('app.inactive') . '</span>';
                }
                return '<span class="badge bg-danger">' . __('app.suspended') . '</span>';
            })
            ->editColumn('created_at', function($user) {
                return $user->created_at->format('M d, Y');
            })
            ->addColumn('actions', function($user) {
                $actions = '<div class="btn-group btn-group-sm">';
                if (auth()->user()->can('view user profile')) {
                    $actions .= '<a href="' . route('users.show', $user) . '" class="btn btn-outline-info" title="View Profile"><i class="bx bx-show"></i></a>';
                }
                if (auth()->user()->can('edit user')) {
                    $actions .= '<a href="' . route('users.edit', $user) . '" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                if (auth()->user()->can('delete user')) {
                    $hasGL = \App\Models\GlTransaction::where('user_id', $user->id)->exists();
                    if ($hasGL) {
                        $actions .= '<button class="btn btn-outline-danger" title="Cannot delete: User has GL transactions." disabled><i class="bx bx-lock"></i></button>';
                    } else {
                        $csrfToken = csrf_token();
                        $actions .= '<form action="' . route('users.destroy', $user) . '" method="POST" style="display:inline-block;" class="delete-form">'
                            . '<input type="hidden" name="_token" value="' . $csrfToken . '">'
                            . '<input type="hidden" name="_method" value="DELETE">'
                            . '<button type="submit" class="btn btn-outline-danger" title="Delete" data-name="' . e($user->name) . '"><i class="bx bx-trash"></i></button>'
                            . '</form>';
                    }
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['name_display', 'roles_badges', 'status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return view('users.form', compact('roles'));
    }

    public function store(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('User creation request started', [
            'request_data' => $request->except(['password', 'password_confirmation']),
            'user_id' => auth()->id(),
            'company_id' => current_company_id()
        ]);

        try {
            DB::beginTransaction();

            // Validate the request - direct user creation only
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,NULL,id,company_id,' . current_company_id(),
                'phone' => 'required|string|max:20|unique:users,phone,NULL,id,company_id,' . current_company_id(),
                'role_id' => 'required|exists:roles,id',
                'status' => 'required|in:active,inactive',
                'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordValidation(null)],
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                \Log::warning('User creation validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->except(['password', 'password_confirmation'])
                ]);

                DB::rollBack();
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            \Log::info('User creation validation passed');

            // Create user directly
            $user = User::create([
                    'name' => $request->name,
                    'phone' => $this->formatPhoneNumber($request->phone),
                    'email' => $request->email,
                    'password' => Hash::make($request->password), // Temporary, will be updated by PasswordService
                    'company_id' => current_company_id(),
                    'status' => $request->status,
                    'is_active' => $request->status === 'active' ? 'yes' : 'no',
                ]);
                
            // Use PasswordService to properly set password with history tracking
            $passwordService = new PasswordService();
            $passwordService->updatePassword($user, $request->password);

            // Verify the role exists and assign it
            $role = Role::find($request->role_id);
            if (!$role) {
                DB::rollBack();
                \Log::error('Role not found during user creation', [
                    'role_id' => $request->role_id
                ]);

                return redirect()->back()
                    ->withErrors(['role_id' => 'Selected role not found.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            // Assign role to user
            $user->assignRole($role);

            DB::commit();

            \Log::info('User created successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return redirect()->route('users.index')
                ->with('success', 'User created successfully!');

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Database error during user creation', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Database error occurred. Please try again.'])
                ->withInput($request->except(['password', 'password_confirmation']));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Unexpected error during user creation', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'An unexpected error occurred. Please try again.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    public function show(User $user)
    {
        // Ensure user belongs to current company (only when both are set)
        if ($user->company_id && auth()->user()->company_id && $user->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Load user relationships including branches and locations
        $user->load(['branches', 'company', 'roles', 'permissions', 'locations']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        // Ensure user belongs to current company (only when both are set)
        if ($user->company_id && auth()->user()->company_id && $user->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $user->load('roles');

        return view('users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        // Ensure user belongs to current company (only when both are set)
        if ($user->company_id && auth()->user()->company_id && $user->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        try {
            DB::beginTransaction();

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id . ',id,company_id,' . current_company_id(),
                'phone' => 'required|string|max:20|unique:users,phone,' . $user->id . ',id,company_id,' . current_company_id(),
                'role_id' => 'required|exists:roles,id',
                'status' => 'required|in:active,inactive',
            ];

            if ($request->filled('password')) {
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed', new PasswordValidation($user)];
            }

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            $userData = [
                'name' => $request->name,
                'phone' => $this->formatPhoneNumber($request->phone),
                'email' => $request->email,
                'status' => $request->status,
                'is_active' => $request->status === 'active' ? 'yes' : 'no',
            ];

            $user->update($userData);

            // Update password if provided using PasswordService
            if ($request->filled('password')) {
                $passwordService = new PasswordService();
                $passwordService->updatePassword($user, $request->password);
                \Log::info('Password updated for user using PasswordService', ['user_id' => $user->id]);
            }

            // Verify the role exists and assign it
            $role = Role::find($request->role_id);
            if (!$role) {
                DB::rollBack();
                \Log::error('Role not found during user update', [
                    'user_id' => $user->id,
                    'role_id' => $request->role_id
                ]);

                return redirect()->back()
                    ->withErrors(['role_id' => 'Selected role not found.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            // Sync roles
            $user->syncRoles([$role]);

            DB::commit();

            \Log::info('User updated successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully!');

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Database error during user update', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Database error occurred. Please try again.'])
                ->withInput($request->except(['password', 'password_confirmation']));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Unexpected error during user update', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'An unexpected error occurred. Please try again.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    public function destroy(User $user)
    {
        // Ensure user belongs to current company (only when both are set)
        if ($user->company_id && current_company_id() && $user->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        // Prevent deletion of own account
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    public function profile()
    {
        $user = auth()->user();
        $user->load(['branches', 'company', 'roles']);

        return view('users.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // Custom validation for email to handle existing email
        $emailRules = 'nullable|email';
        if ($request->email !== $user->email) {
            $emailRules .= '|unique:users,email,' . $user->id . ',id,company_id,' . current_company_id();
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id . ',id,company_id,' . current_company_id(),
            'email' => $emailRules,
            'password' => ['nullable', 'string', 'min:8', 'confirmed', new PasswordValidation($user)],
        ]);

        $userData = [
            'name' => $request->name,
            'phone' => $this->formatPhoneNumber($request->phone),
            'email' => $request->email,
        ];

        $user->update($userData);

        // Update password if provided using PasswordService
        if ($request->filled('password')) {
            $passwordService = new PasswordService();
            $passwordService->updatePassword($user, $request->password);
        }

        return redirect()->route('users.profile')->with('success', 'Profile updated successfully!');
    }

    public function changeStatus(User $user)
    {
        // Ensure user belongs to current company (only when both are set)
        if ($user->company_id && current_company_id() && $user->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update([
            'status' => $newStatus,
            'is_active' => $newStatus === 'active' ? 'yes' : 'no'
        ]);

        return redirect()->route('users.index')->with('success', "User status changed to {$newStatus}!");
    }

    public function assignRoles(Request $request, User $user)
    {
        // Ensure user belongs to current company
        if ($user->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = Role::whereIn('id', $request->roles)->get();
        $user->syncRoles($roles);

        return redirect()->route('users.edit', $user)->with('success', 'Roles assigned successfully!');
    }

   public function assignBranches(Request $request, User $user)
    {
        $actor = auth()->user();

        // Ensure user belongs to same company as actor
        if ($actor->company_id && $user->company_id && $actor->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'branches' => 'array',
            'branches.*' => 'exists:branches,id',
        ]);

        // Only allow branches from the actor's company
        $companyId = $actor->company_id;
        if ($companyId) {
            $validBranches = \App\Models\Branch::where('company_id', $companyId)
                ->whereIn('id', $request->branches ?? [])
                ->pluck('id')
                ->toArray();

            $user->branches()->sync($validBranches);
        } else {
            $user->branches()->sync($request->branches ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Branches assigned successfully.'
        ]);
    }

    public function assignLocations(Request $request, User $user)
    {
        $actor = auth()->user();

        \Log::info('Location assignment request', [
            'user_id' => $user->id,
            'user_company_id' => $user->company_id,
            'actor_company_id' => $actor->company_id,
            'current_company_id' => current_company_id(),
            'request_locations' => $request->locations ?? [],
            'actor_id' => auth()->id()
        ]);

        // Allow if user is super admin or if both users are in the same company
        if (!($actor && ($actor->hasRole('super-admin') ||
            ($actor->company_id && $user->company_id && $actor->company_id === $user->company_id) ||
            (!$user->company_id && $actor->company_id)))) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'locations' => 'required|array|min:1',
            'locations.*' => 'exists:inventory_locations,id',
            'default_location_id' => 'nullable|exists:inventory_locations,id',
        ]);

        // Use actor's company_id instead of current_company_id() since it's null
        $companyId = $actor->company_id;
        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'No company assigned to user.'], 400);
        }

        // Only allow locations from the actor's company
        $locations = \App\Models\InventoryLocation::where('company_id', $companyId)
            ->whereIn('id', $request->locations)
            ->pluck('id')
            ->all();

        if (empty($locations)) {
            return response()->json(['success' => false, 'message' => 'No valid locations selected for this company.'], 400);
        }

        // Sync without is_default first
        $syncData = array_fill_keys($locations, ['is_default' => false]);
        $user->locations()->sync($syncData);

        // Set default if provided and in selected list
        if ($request->filled('default_location_id') && in_array((int)$request->default_location_id, $locations)) {
            // Reset all defaults
            \DB::table('location_user')->where('user_id', $user->id)->update(['is_default' => false]);
            // Set one default
            \DB::table('location_user')->where('user_id', $user->id)->where('inventory_location_id', $request->default_location_id)->update(['is_default' => true]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Locations assigned successfully.']);
        }

        return back()->with('success', 'Locations assigned successfully.');
    }

    public function removeLocation(User $user, \App\Models\InventoryLocation $location)
    {
        $actor = auth()->user();
        if (!($actor && ($actor->company_id === $user->company_id || $actor->hasRole('super-admin')))) {
            abort(403, 'Unauthorized access.');
        }
        $user->locations()->detach($location->id);
        return back()->with('success', 'Location removed successfully.');
    }

    public function setDefaultLocation(Request $request, User $user)
    {
        $actor = auth()->user();
        if (!($actor && ($actor->company_id === $user->company_id || $actor->hasRole('super-admin')))) {
            abort(403, 'Unauthorized access.');
        }
        $request->validate(['location_id' => 'required|exists:inventory_locations,id']);
        // Ensure location belongs to same company and user has it assigned
        $locationId = (int)$request->location_id;
        $exists = \DB::table('location_user')->where('user_id', $user->id)->where('inventory_location_id', $locationId)->exists();
        if (!$exists) {
            return back()->withErrors(['location_id' => 'User is not assigned to this location.']);
        }
        \DB::table('location_user')->where('user_id', $user->id)->update(['is_default' => false]);
        \DB::table('location_user')->where('user_id', $user->id)->where('inventory_location_id', $locationId)->update(['is_default' => true]);
        return back()->with('success', 'Default location updated.');
    }

    /**
     * Format phone number to 255 format
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any spaces, dashes, or other characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If starts with +255, remove the +
        if (str_starts_with($phone, '+255')) {
            return substr($phone, 1);
        }

        // If starts with 0, remove 0 and add 255
        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }

        // If already starts with 255, return as is
        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        // If it's a 9-digit number (Tanzania mobile), add 255
        if (strlen($phone) === 9) {
            return '255' . $phone;
        }

        // Return as is if no pattern matches
        return $phone;
    }

    /**
     * Get employees for dropdown
     */
    public function employees()
    {
        $employees = User::where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($employees);
    }
}
