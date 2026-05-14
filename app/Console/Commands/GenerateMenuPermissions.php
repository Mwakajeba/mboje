<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Menu;
use App\Models\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;

class GenerateMenuPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menus:generate-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions for all menus and link them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating menu permissions...');
        
        $menus = Menu::all();
        $created = 0;
        $updated = 0;
        
        foreach ($menus as $menu) {
            // Generate permission name from menu name
            $permissionName = $this->generatePermissionName($menu);
            
            // Create permission in Spatie table
            $permission = SpatiePermission::firstOrCreate(
                [
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]
            );
            
            // Also create in custom Permission table
            Permission::firstOrCreate(
                [
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]
            );
            
            // Update menu with permission name
            if ($menu->permission_name !== $permissionName) {
                $menu->permission_name = $permissionName;
                $menu->save();
                $updated++;
            } else {
                $created++;
            }
            
            $this->line("  ✓ {$menu->name} → {$permissionName}");
        }
        
        $this->info("\nCompleted!");
        $this->info("Created/Updated: {$updated} menus");
        $this->info("Already linked: {$created} menus");
        
        return Command::SUCCESS;
    }
    
    /**
     * Generate permission name from menu name
     */
    private function generatePermissionName(Menu $menu): string
    {
        // Convert menu name to permission format
        // e.g., "Sales Management" -> "access sales management menu"
        $name = strtolower($menu->name);
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        return "access {$name} menu";
    }
}
