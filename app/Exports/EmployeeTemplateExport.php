<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        $employees = [];
        
        // Sample employee data - 50 diverse employees
        $sampleData = [
            ['John', 'Michael', 'Doe', 'john.doe@company.com', '0712345678', '1990-01-15', 'male', 'single', 'Software Engineer', '1500000', '19900115-12345-67890-12', 'IT Department', 'Software Developer', 'CRDB Bank', '0150123456789', '123-456-789'],
            ['Mary', 'Jane', 'Smith', 'mary.smith@company.com', '0723456789', '1988-03-22', 'female', 'married', 'HR Manager', '2000000', '19880322-23456-78901-23', 'Human Resources', 'HR Manager', 'NBC Bank', '0151234567890', '234-567-890'],
            ['Peter', 'James', 'Johnson', 'peter.johnson@company.com', '0734567890', '1985-07-10', 'male', 'married', 'Accountant', '1800000', '19850710-34567-89012-34', 'Finance', 'Senior Accountant', 'NMB Bank', '0152345678901', '345-678-901'],
            ['Sarah', 'Elizabeth', 'Williams', 'sarah.williams@company.com', '0745678901', '1992-12-05', 'female', 'single', 'Marketing Specialist', '1400000', '19921205-45678-90123-45', 'Marketing', 'Marketing Executive', 'Stanbic Bank', '0153456789012', '456-789-012'],
            ['David', 'Robert', 'Brown', 'david.brown@company.com', '0756789012', '1987-09-18', 'male', 'divorced', 'Operations Manager', '2200000', '19870918-56789-01234-56', 'Operations', 'Operations Manager', 'DTB Bank', '0154567890123', '567-890-123'],
            ['Grace', 'Amelia', 'Davis', 'grace.davis@company.com', '0767890123', '1991-04-30', 'female', 'single', 'Procurement Officer', '1300000', '19910430-67890-12345-67', 'Procurement', 'Procurement Officer', 'Exim Bank', '0155678901234', '678-901-234'],
            ['Michael', 'Anthony', 'Miller', 'michael.miller@company.com', '0778901234', '1989-11-12', 'male', 'married', 'Security Supervisor', '1100000', '19891112-78901-23456-78', 'Security', 'Security Supervisor', 'CRDB Bank', '0156789012345', '789-012-345'],
            ['Jennifer', 'Rose', 'Wilson', 'jennifer.wilson@company.com', '0789012345', '1993-08-25', 'female', 'married', 'Sales Representative', '1200000', '19930825-89012-34567-89', 'Sales', 'Sales Executive', 'NBC Bank', '0157890123456', '890-123-456'],
            ['Christopher', 'Paul', 'Moore', 'christopher.moore@company.com', '0790123456', '1986-06-14', 'male', 'single', 'IT Support', '1000000', '19860614-90123-45678-90', 'IT Department', 'IT Support Specialist', 'NMB Bank', '0158901234567', '901-234-567'],
            ['Amanda', 'Nicole', 'Taylor', 'amanda.taylor@company.com', '0701234567', '1994-02-28', 'female', 'single', 'Administrative Assistant', '900000', '19940228-01234-56789-01', 'Administration', 'Admin Assistant', 'Stanbic Bank', '0159012345678', '012-345-678'],
            
            ['Robert', 'Charles', 'Anderson', 'robert.anderson@company.com', '0712345679', '1984-05-16', 'male', 'married', 'Project Manager', '2500000', '19840516-12345-67890-13', 'Projects', 'Senior Project Manager', 'DTB Bank', '0160123456789', '123-456-780'],
            ['Lisa', 'Marie', 'Thomas', 'lisa.thomas@company.com', '0723456780', '1990-10-03', 'female', 'divorced', 'Quality Assurance', '1600000', '19901003-23456-78901-24', 'Quality', 'QA Manager', 'Exim Bank', '0161234567890', '234-567-891'],
            ['William', 'John', 'Jackson', 'william.jackson@company.com', '0734567891', '1988-01-20', 'male', 'single', 'Production Supervisor', '1700000', '19880120-34567-89012-35', 'Production', 'Production Supervisor', 'CRDB Bank', '0162345678901', '345-678-902'],
            ['Susan', 'Catherine', 'White', 'susan.white@company.com', '0745678902', '1992-07-07', 'female', 'married', 'Customer Service Rep', '1000000', '19920707-45678-90123-46', 'Customer Service', 'Customer Service Rep', 'NBC Bank', '0163456789012', '456-789-013'],
            ['James', 'William', 'Harris', 'james.harris@company.com', '0756789013', '1987-12-11', 'male', 'married', 'Logistics Coordinator', '1300000', '19871211-56789-01234-57', 'Logistics', 'Logistics Coordinator', 'NMB Bank', '0164567890123', '567-890-124'],
            ['Karen', 'Lynn', 'Martin', 'karen.martin@company.com', '0767890124', '1991-09-24', 'female', 'single', 'Research Analyst', '1400000', '19910924-67890-12345-68', 'Research', 'Research Analyst', 'Stanbic Bank', '0165678901234', '678-901-235'],
            ['Mark', 'Steven', 'Thompson', 'mark.thompson@company.com', '0778901235', '1989-04-02', 'male', 'divorced', 'Maintenance Technician', '1100000', '19890402-78901-23456-79', 'Maintenance', 'Senior Technician', 'DTB Bank', '0166789012345', '789-012-346'],
            ['Nancy', 'Ruth', 'Garcia', 'nancy.garcia@company.com', '0789012346', '1993-11-15', 'female', 'married', 'Training Coordinator', '1250000', '19931115-89012-34567-90', 'Training', 'Training Coordinator', 'Exim Bank', '0167890123456', '890-123-457'],
            ['Daniel', 'Matthew', 'Martinez', 'daniel.martinez@company.com', '0790123457', '1986-08-08', 'male', 'single', 'Network Administrator', '1550000', '19860808-90123-45678-91', 'IT Department', 'Network Admin', 'CRDB Bank', '0168901234567', '901-234-568'],
            ['Betty', 'Jean', 'Robinson', 'betty.robinson@company.com', '0701234568', '1994-03-19', 'female', 'single', 'Receptionist', '850000', '19940319-01234-56789-02', 'Administration', 'Receptionist', 'NBC Bank', '0169012345678', '012-345-679'],
            
            ['Anthony', 'Joseph', 'Clark', 'anthony.clark@company.com', '0712345680', '1985-06-26', 'male', 'married', 'Financial Analyst', '1900000', '19850626-12345-67890-14', 'Finance', 'Financial Analyst', 'NMB Bank', '0170123456789', '123-456-781'],
            ['Helen', 'Ann', 'Rodriguez', 'helen.rodriguez@company.com', '0723456791', '1990-12-13', 'female', 'married', 'Legal Advisor', '2100000', '19901213-23456-78901-25', 'Legal', 'Legal Advisor', 'Stanbic Bank', '0171234567890', '234-567-892'],
            ['Charles', 'Richard', 'Lewis', 'charles.lewis@company.com', '0734567892', '1988-05-01', 'male', 'single', 'Warehouse Manager', '1650000', '19880501-34567-89012-36', 'Warehouse', 'Warehouse Manager', 'DTB Bank', '0172345678901', '345-678-903'],
            ['Dorothy', 'Frances', 'Lee', 'dorothy.lee@company.com', '0745678903', '1992-10-17', 'female', 'divorced', 'Compliance Officer', '1750000', '19921017-45678-90123-47', 'Compliance', 'Compliance Officer', 'Exim Bank', '0173456789012', '456-789-014'],
            ['Paul', 'Christopher', 'Walker', 'paul.walker@company.com', '0756789014', '1987-02-04', 'male', 'married', 'Safety Officer', '1350000', '19870204-56789-01234-58', 'Safety', 'Safety Officer', 'CRDB Bank', '0174567890123', '567-890-125'],
            ['Sandra', 'Kay', 'Hall', 'sandra.hall@company.com', '0767890125', '1991-07-21', 'female', 'single', 'Event Coordinator', '1200000', '19910721-67890-12345-69', 'Events', 'Event Coordinator', 'NBC Bank', '0175678901234', '678-901-236'],
            ['Steven', 'Alan', 'Allen', 'steven.allen@company.com', '0778901236', '1989-01-14', 'male', 'married', 'Database Administrator', '1800000', '19890114-78901-23456-80', 'IT Department', 'Database Admin', 'NMB Bank', '0176789012345', '789-012-347'],
            ['Donna', 'Louise', 'Young', 'donna.young@company.com', '0789012347', '1993-06-09', 'female', 'single', 'Public Relations', '1450000', '19930609-89012-34567-91', 'Public Relations', 'PR Specialist', 'Stanbic Bank', '0177890123456', '890-123-458'],
            ['Kenneth', 'Edward', 'Hernandez', 'kenneth.hernandez@company.com', '0790123458', '1986-11-27', 'male', 'divorced', 'Audit Manager', '2300000', '19861127-90123-45678-92', 'Audit', 'Audit Manager', 'DTB Bank', '0178901234567', '901-234-569'],
            ['Carol', 'Joyce', 'King', 'carol.king@company.com', '0701234569', '1994-04-12', 'female', 'married', 'Secretary', '950000', '19940412-01234-56789-03', 'Administration', 'Executive Secretary', 'Exim Bank', '0179012345678', '012-345-680'],
            
            ['Brian', 'Kevin', 'Wright', 'brian.wright@company.com', '0712345681', '1984-09-05', 'male', 'single', 'Business Analyst', '1950000', '19840905-12345-67890-15', 'Business Analysis', 'Senior Business Analyst', 'CRDB Bank', '0180123456789', '123-456-782'],
            ['Ruth', 'Diane', 'Lopez', 'ruth.lopez@company.com', '0723456792', '1990-03-18', 'female', 'married', 'Inventory Manager', '1600000', '19900318-23456-78901-26', 'Inventory', 'Inventory Manager', 'NBC Bank', '0181234567890', '234-567-893'],
            ['George', 'Kenneth', 'Hill', 'george.hill@company.com', '0734567893', '1988-08-31', 'male', 'married', 'Facilities Manager', '1700000', '19880831-34567-89012-37', 'Facilities', 'Facilities Manager', 'NMB Bank', '0182345678901', '345-678-904'],
            ['Sharon', 'Carol', 'Scott', 'sharon.scott@company.com', '0745678904', '1992-01-06', 'female', 'single', 'Budget Analyst', '1400000', '19920106-45678-90123-48', 'Finance', 'Budget Analyst', 'Stanbic Bank', '0183456789012', '456-789-015'],
            ['Jason', 'Timothy', 'Green', 'jason.green@company.com', '0756789015', '1987-05-23', 'male', 'divorced', 'Technical Writer', '1300000', '19870523-56789-01234-59', 'Documentation', 'Technical Writer', 'DTB Bank', '0184567890123', '567-890-126'],
            ['Michelle', 'Angela', 'Adams', 'michelle.adams@company.com', '0767890126', '1991-10-10', 'female', 'married', 'Training Specialist', '1250000', '19911010-67890-12345-70', 'Training', 'Training Specialist', 'Exim Bank', '0185678901234', '678-901-237'],
            ['Gary', 'Scott', 'Baker', 'gary.baker@company.com', '0778901237', '1989-12-02', 'male', 'single', 'Systems Analyst', '1750000', '19891202-78901-23456-81', 'IT Department', 'Systems Analyst', 'CRDB Bank', '0186789012345', '789-012-348'],
            ['Cynthia', 'Patricia', 'Gonzalez', 'cynthia.gonzalez@company.com', '0789012348', '1993-07-15', 'female', 'single', 'Cost Accountant', '1500000', '19930715-89012-34567-92', 'Finance', 'Cost Accountant', 'NBC Bank', '0187890123456', '890-123-459'],
            ['Frank', 'Raymond', 'Nelson', 'frank.nelson@company.com', '0790123459', '1986-04-08', 'male', 'married', 'Plant Manager', '2400000', '19860408-90123-45678-93', 'Production', 'Plant Manager', 'NMB Bank', '0188901234567', '901-234-570'],
            ['Laura', 'Susan', 'Carter', 'laura.carter@company.com', '0701234570', '1994-09-21', 'female', 'married', 'Office Manager', '1100000', '19940921-01234-56789-04', 'Administration', 'Office Manager', 'Stanbic Bank', '0189012345678', '012-345-681'],
            
            ['Scott', 'Douglas', 'Mitchell', 'scott.mitchell@company.com', '0712345682', '1985-02-14', 'male', 'single', 'Supply Chain Manager', '2000000', '19850214-12345-67890-16', 'Supply Chain', 'Supply Chain Manager', 'DTB Bank', '0190123456789', '123-456-783'],
            ['Maria', 'Elena', 'Perez', 'maria.perez@company.com', '0723456793', '1990-11-07', 'female', 'married', 'Quality Control', '1350000', '19901107-23456-78901-27', 'Quality', 'QC Inspector', 'Exim Bank', '0191234567890', '234-567-894'],
            ['Raymond', 'Arthur', 'Roberts', 'raymond.roberts@company.com', '0734567894', '1988-06-20', 'male', 'divorced', 'Production Planner', '1600000', '19880620-34567-89012-38', 'Production', 'Production Planner', 'CRDB Bank', '0192345678901', '345-678-905'],
            ['Kimberly', 'Marie', 'Turner', 'kimberly.turner@company.com', '0745678905', '1992-03-03', 'female', 'single', 'Payroll Specialist', '1200000', '19920303-45678-90123-49', 'Human Resources', 'Payroll Specialist', 'NBC Bank', '0193456789012', '456-789-016'],
            ['Jerry', 'Wayne', 'Phillips', 'jerry.phillips@company.com', '0756789016', '1987-08-16', 'male', 'married', 'Shipping Coordinator', '1150000', '19870816-56789-01234-60', 'Shipping', 'Shipping Coordinator', 'NMB Bank', '0194567890123', '567-890-127'],
            ['Amy', 'Christine', 'Campbell', 'amy.campbell@company.com', '0767890127', '1991-01-29', 'female', 'married', 'Data Entry Clerk', '800000', '19910129-67890-12345-71', 'Data Processing', 'Data Entry Clerk', 'Stanbic Bank', '0195678901234', '678-901-238'],
            ['Ralph', 'Harold', 'Parker', 'ralph.parker@company.com', '0778901238', '1989-05-12', 'male', 'single', 'Maintenance Manager', '1850000', '19890512-78901-23456-82', 'Maintenance', 'Maintenance Manager', 'DTB Bank', '0196789012345', '789-012-349'],
            ['Angela', 'Debra', 'Evans', 'angela.evans@company.com', '0789012349', '1993-10-25', 'female', 'divorced', 'Benefits Coordinator', '1300000', '19931025-89012-34567-93', 'Human Resources', 'Benefits Coordinator', 'Exim Bank', '0197890123456', '890-123-460'],
            ['Bruce', 'Carl', 'Edwards', 'bruce.edwards@company.com', '0790123460', '1986-07-18', 'male', 'married', 'Risk Manager', '2200000', '19860718-90123-45678-94', 'Risk Management', 'Risk Manager', 'CRDB Bank', '0198901234567', '901-234-571'],
            ['Debra', 'Joan', 'Collins', 'debra.collins@company.com', '0701234571', '1994-12-01', 'female', 'single', 'File Clerk', '750000', '19941201-01234-56789-05', 'Administration', 'File Clerk', 'NBC Bank', '0199012345678', '012-345-682']
        ];

        for ($i = 0; $i < 50; $i++) {
            $data = $sampleData[$i];
            $empNumber = 'EMP' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            
            // Vary employment types to show available options
            $employmentTypes = ['full_time', 'part_time', 'contract', 'intern'];
            $employmentType = $employmentTypes[$i % 4];
            
            $employees[] = [
                $empNumber, // employee_number
                $data[0], // first_name
                $data[1], // middle_name
                $data[2], // last_name
                $data[3], // email
                $data[4], // phone_number
                $data[5], // date_of_birth
                $data[6], // gender
                $data[7], // marital_status
                'Tanzania', // country
                'Dar es Salaam', // region
                'Kinondoni', // district
                'Mikocheni Area, Dar es Salaam', // current_physical_location
                $data[9], // basic_salary
                'national_id', // identity_document_type
                $data[10], // identity_number
                $employmentType, // employment_type (valid enum: full_time, part_time, contract, intern)
                '2024-01-01', // date_of_employment
                $data[15], // tin
                $data[13], // bank_name
                $data[14], // bank_account_number
                $data[11], // department_name
                $data[12], // position_title
                'Head Office', // branch_name
            ];
        }
        
        return $employees;
    }

    public function headings(): array
    {
        return [
            'employee_number',
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'phone_number',
            'date_of_birth',
            'gender',
            'marital_status',
            'country',
            'region',
            'district',
            'current_physical_location',
            'basic_salary',
            'identity_document_type',
            'identity_number',
            'employment_type',
            'date_of_employment',
            'tin',
            'bank_name',
            'bank_account_number',
            'department_name',
            'position_title',
            'branch_name',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
