<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\InventoryLocation;

class LocationUserSeeder extends Seeder
{
    public function run(): void
    {
        // Attach inventory locations to users so they have assigned locations on first login
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $locations = InventoryLocation::all(['id','branch_id']);
        if ($locations->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Prefer locations in the user's branch when available; else attach all locations
            $branchLocations = $locations->where('branch_id', $user->branch_id)->pluck('id')->all();
            $attachIds = !empty($branchLocations) ? $branchLocations : $locations->pluck('id')->all();
            // syncWithoutDetaching to avoid duplicates if rerun
            if (method_exists($user, 'locations')) {
                $user->locations()->syncWithoutDetaching($attachIds);
            }
        }
    }
}


