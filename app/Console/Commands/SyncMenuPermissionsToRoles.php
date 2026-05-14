<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class SyncMenuPermissionsToRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menus:sync-permissions-to-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing menu_role assignments to menu permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing menu permissions to roles based on existing menu_role assignments...');
        
        $roles = Role::all();
        $totalSynced = 0;
        
        foreach ($roles as $role) {
            $this->line("\nProcessing role: {$role->name}");
            
            // Get all menus assigned to this role via menu_role
            $menuIds = DB::table('menu_role')
                ->where('role_id', $role->id)
                ->pluck('menu_id');
            
            if ($menuIds->isEmpty()) {
                $this->line("  No menus assigned via menu_role");
                continue;
            }
            
            // Get menus with their permission names
            $menus = Menu::whereIn('id', $menuIds)
                ->whereNotNull('permission_name')
                ->get();
            
            if ($menus->isEmpty()) {
                $this->line("  No menus with permission_name found");
                continue;
            }
            
            // Get permission names
            $permissionNames = $menus->pluck('permission_name')->toArray();
            
            // Assign permissions to role
            $role->givePermissionTo($permissionNames);
            
            $synced = count($permissionNames);
            $totalSynced += $synced;
            $this->line("  ✓ Synced {$synced} menu permissions");
        }
        
        $this->info("\nCompleted!");
        $this->info("Total permissions synced: {$totalSynced}");
        
        return Command::SUCCESS;
    }
}
