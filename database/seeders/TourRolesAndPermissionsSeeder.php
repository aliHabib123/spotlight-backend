<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class TourRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Tour-related permissions
        $permissions = [
            // Tour management
            'view tours',
            'create tours',
            'edit tours',
            'delete tours',
            'approve tours',
            'feature tours',
            
            // Tour location management
            'view tour-locations',
            'create tour-locations',
            'edit tour-locations',
            'delete tour-locations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Grant Tour permissions to existing roles
        $superAdminRole = Role::findByName('super admin');
        $superAdminRole->givePermissionTo([
            'view tours', 'create tours', 'edit tours', 'delete tours', 'approve tours', 'feature tours',
            'view tour-locations', 'create tour-locations', 'edit tour-locations', 'delete tour-locations',
        ]);

        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo([
            'view tours', 'edit tours', 'delete tours', 'approve tours', 'feature tours',
            'view tour-locations', 'create tour-locations', 'edit tour-locations', 'delete tour-locations',
        ]);

        // Create Tour Admin role
        $tourAdminRole = Role::firstOrCreate(['name' => 'tour admin']);
        $tourAdminRole->givePermissionTo([
            'view tours', 'create tours', 'edit tours',
            // Removed 'view tour-locations'
        ]);

        // Create Tour Admin user for demonstration
        $tourAdmin = User::firstOrCreate(
            ['email' => 'touradmin@example.com'],
            [
                'name' => 'Tour Admin',
                'username' => 'touradmin',
                'password' => Hash::make('password'),
            ]
        );
        $tourAdmin->assignRole('tour admin');
    }
}
