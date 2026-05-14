<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Hr\Employee;

// Simulate what the controller does
$instructors = Employee::where('status', 'active')
    ->orderBy('first_name')
    ->get();

echo "Instructors count: " . $instructors->count() . "\n\n";

foreach($instructors as $instructor) {
    echo "ID: {$instructor->id} | full_name: {$instructor->full_name}\n";
}
