<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Hr\Employee;

$employees = Employee::where('status', 'active')->get();
echo "Total active employees: " . $employees->count() . "\n\n";

foreach($employees->take(5) as $emp) {
    echo "ID: {$emp->id} | Name: {$emp->full_name} | Status: {$emp->status}\n";
}

// Check all employees regardless of status
$allEmployees = Employee::all();
echo "\nTotal ALL employees: " . $allEmployees->count() . "\n";
foreach($allEmployees->take(5) as $emp) {
    echo "ID: {$emp->id} | Name: {$emp->full_name} | Status: {$emp->status}\n";
}
