<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Hr\Employee;

$emps = Employee::all();
echo "Employees and their companies:\n";
foreach($emps as $e) {
    echo "ID: {$e->id} | Company: {$e->company_id} | Name: {$e->full_name}\n";
}

echo "\nSession company_id would be: " . (session('company_id') ?? 'NULL') . "\n";
