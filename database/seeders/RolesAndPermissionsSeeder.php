<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Shop management
            'view shops',
            'create shops',
            'edit shops',
            'delete shops',
            
            // Product management
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Order management
            'view orders',
            'process orders',
            'cancel orders',
            
            // Financial operations
            'view finances',
            'process payments',
            'issue refunds',
            
            // Spotlight management
            'view spotlights',
            'create spotlights',
            'edit spotlights',
            'delete spotlights',
            'publish spotlights',
            'feature spotlights',
            
            // Category management
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Tag management
            'view tags',
            'create tags',
            'edit tags',
            'delete tags',
            
            // Location management
            'view locations',
            'create locations',
            'edit locations',
            'delete locations',
            
            // Attribute management
            'view attributes',
            'create attributes',
            'edit attributes',
            'delete attributes',
            
            // Media management
            'view media',
            'upload media',
            'edit media',
            'delete media',
            
            // Feature flag management
            'view feature-flags',
            'edit feature-flags',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - gets all permissions
        $superAdminRole = Role::create(['name' => 'super admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Admin - gets most permissions except financial operations
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view users', 'create users', 'edit users',
            'view shops', 'create shops', 'edit shops', 'delete shops',
            'view products', 'create products', 'edit products', 'delete products',
            'view orders', 'process orders', 'cancel orders',
            'view finances',
            'view spotlights', 'create spotlights', 'edit spotlights', 'delete spotlights', 'publish spotlights', 'feature spotlights',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view tags', 'create tags', 'edit tags', 'delete tags',
            'view locations', 'create locations', 'edit locations', 'delete locations',
            'view attributes', 'create attributes', 'edit attributes', 'delete attributes',
            'view media', 'upload media', 'edit media', 'delete media',
            'view feature-flags',
        ]);

        // Finance - gets financial permissions
        $financeRole = Role::create(['name' => 'finance']);
        $financeRole->givePermissionTo([
            'view users',
            'view shops',
            'view products',
            'view orders',
            'view finances', 'process payments', 'issue refunds',
            'view spotlights',
            'view categories',
            'view tags',
            'view locations',
            'view attributes',
            'view media',
            'view feature-flags',
        ]);

        // App User - basic permissions
        $appUserRole = Role::create(['name' => 'app user']);
        $appUserRole->givePermissionTo([
            'view shops',
            'view products',
            'view spotlights',
            'view categories',
            'view tags',
            'view locations',
            'view media',
        ]);

        // Create Super Admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('super admin');

        // Create Admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        // Create Finance user
        $finance = User::create([
            'name' => 'Finance User',
            'email' => 'finance@example.com',
            'password' => Hash::make('password'),
        ]);
        $finance->assignRole('finance');

        // Create App User
        $appUser = User::create([
            'name' => 'App User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $appUser->assignRole('app user');
    }
}
