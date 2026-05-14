# Migration Instructions

To fix the "Table 'rental_approvals' doesn't exist" error, run the following migration:

```bash
php artisan migrate --path=database/migrations/2026_02_04_000001_create_rental_approvals_table.php
```

Or run all pending migrations:

```bash
php artisan migrate
```

This will create the `rental_approvals` table needed for the approval system.
