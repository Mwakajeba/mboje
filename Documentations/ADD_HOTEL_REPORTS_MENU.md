# Adding Hotel Reports Menu Item

The Hotel Reports menu item has been added to the MenuSeeder, but you need to run the seeder to add it to your database.

## Option 1: Run the MenuSeeder (Recommended)

Run this command in your terminal:

```bash
php artisan db:seed --class=MenuSeeder
```

This will add the "Hotel Reports" menu item under the Reports menu.

## Option 2: Run the PHP Script

Alternatively, you can run the PHP script I created:

```bash
php add_hotel_reports_menu.php
```

## Option 3: Manual Database Entry

If the above options don't work, you can manually add it to the database:

1. Find the Reports menu ID:
   ```sql
   SELECT id FROM menus WHERE name = 'Reports' AND parent_id IS NULL;
   ```

2. Insert the Hotel Reports menu item:
   ```sql
   INSERT INTO menus (name, route, parent_id, icon, created_at, updated_at)
   VALUES ('Hotel Reports', 'hotel.reports.index', [REPORTS_MENU_ID], 'bx bx-right-arrow-alt', NOW(), NOW());
   ```

3. Link it to admin and super-admin roles:
   ```sql
   -- Get the menu ID you just created
   SET @menu_id = LAST_INSERT_ID();
   
   -- Get role IDs
   SET @admin_role_id = (SELECT id FROM roles WHERE name = 'admin');
   SET @super_admin_role_id = (SELECT id FROM roles WHERE name = 'super-admin');
   
   -- Link to roles
   INSERT IGNORE INTO menu_role (menu_id, role_id) VALUES (@menu_id, @admin_role_id);
   INSERT IGNORE INTO menu_role (menu_id, role_id) VALUES (@menu_id, @super_admin_role_id);
   ```

After running any of these options, refresh your browser and you should see "Hotel Reports" under the Reports menu.
