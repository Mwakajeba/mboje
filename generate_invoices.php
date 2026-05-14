<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\School\Classe;
use App\Models\School\Student;
use App\Models\FeeGroup;
use App\Models\FeeSetting;
use App\Models\School\AcademicYear;

try {
    // Simulate authentication - set a user
    $user = \App\Models\User::first();
    if (!$user) {
        echo "No user found. Please create a user first.\n";
        exit;
    }
    Auth::login($user);
    session(['branch_id' => null]); // Set branch to null to match fee settings

    echo "Generating fee invoices...\n";

    // Get current academic year
    $academicYear = AcademicYear::where('company_id', $user->company_id)
        ->where('is_current', true)
        ->first();

    if (!$academicYear) {
        echo "No current academic year found.\n";
        exit;
    }

    // Get first class
    $class = Classe::where('company_id', $user->company_id)->first();
    if (!$class) {
        echo "No classes found.\n";
        exit;
    }

    // Get first fee group
    $feeGroup = FeeGroup::where('company_id', $user->company_id)->first();
    if (!$feeGroup) {
        echo "No fee groups found.\n";
        exit;
    }

    // Get active students in the class
    $students = Student::where('class_id', $class->id)
        ->where('academic_year_id', $academicYear->id)
        ->where('status', 'active')
        ->take(5) // Generate for first 5 students
        ->get();

    echo "Found " . $students->count() . " students in class {$class->name}\n";

    $created = 0;
    foreach ($students as $student) {
        // Generate for Quarter 1
        $period = 1;

        // Check if fee setting exists
        $feePeriod = 'Q1';
        $feeSetting = \App\Models\FeeSetting::where('class_id', $class->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('fee_period', $feePeriod)
            ->where('is_active', true)
            ->first();

        if (!$feeSetting) {
            echo "No fee setting found for class {$class->id}, year {$academicYear->id}, period $feePeriod\n";
            continue;
        }

        echo "Found fee setting ID: {$feeSetting->id}, items: {$feeSetting->feeSettingItems->count()}\n";
        $studentCategory = $student->boarding_type ?? 'day';
        echo "Student category: $studentCategory\n";
        $feeItem = $feeSetting->feeSettingItems->where('category', $studentCategory)->first();
        if (!$feeItem) {
            echo "No fee item found for category $studentCategory\n";
            continue;
        }
        echo "Found fee item: {$feeItem->amount}\n";

        // Create invoice using the controller method
        $controller = new \App\Http\Controllers\School\FeeInvoiceController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('createInvoiceForStudent');
        $method->setAccessible(true);

        try {
            $result = $method->invoke($controller, $student, $class->id, $academicYear->id, $period, $feeGroup->id);
            if ($result) {
                $created++;
                echo "Created invoice for {$student->first_name} {$student->last_name}\n";
            } else {
                echo "Failed to create invoice for {$student->first_name} {$student->last_name} (returned false)\n";
            }
        } catch (Exception $e) {
            echo "Exception creating invoice for {$student->first_name}: " . $e->getMessage() . "\n";
        }
    }

    echo "Generated $created invoices.\n";
    echo "Total fee invoices now: " . \App\Models\FeeInvoice::count() . "\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
}