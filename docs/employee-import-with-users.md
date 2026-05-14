# Employee Import with User Account Creation

## Overview
The employee import system now automatically creates user accounts for each imported employee, following the same logic as the manual employee creation process.

## Import Process Flow

### 1. User Account Creation First
For each employee being imported:
- **Full Name**: Combines first_name + middle_name + last_name
- **Email**: Uses provided email (if any)
- **Phone**: Formatted using Tanzania phone number standards
- **Password**: Default set to `password123` (users should change on first login)
- **Role**: Automatically assigned 'employee' role
- **Branch Access**: Assigned to specified branch if provided

### 2. Employee Record Creation
After user creation:
- Links employee record to user via `user_id` field
- Creates all employee-specific data (salary, employment details, etc.)
- Associates with departments, positions, branches as specified

### 3. Transaction Safety
- Each employee import is wrapped in a database transaction
- If user creation fails, employee creation is rolled back
- If employee creation fails, user creation is rolled back
- Ensures data consistency and prevents orphaned records

## Features

### Smart Duplicate Handling
- Checks for duplicate employee numbers
- Checks for duplicate emails in both User and Employee tables
- Provides specific error messages indicating which field conflicts
- Shows existing employee name for easier identification

### Employment Type Mapping
- Accepts common terms: "permanent", "temporary", "internship"
- Maps to valid database enum values: "full_time", "part_time", "contract", "intern"
- Provides fallback to "full_time" for unrecognized values

### Automatic Entity Creation
- **Departments**: Created if they don't exist
- **Positions**: Created if they don't exist
- **User Roles**: Assigns 'employee' role if available
- **Branch Access**: Grants user access to specified branch

## Default User Settings

All imported users are created with:
```php
'password' => 'password123'  // Must be changed on first login
'status' => 'active'
'is_active' => 'yes'
'company_id' => [current_company]
```

## Error Handling

### Validation Errors
- Email format validation
- Required field checking  
- Employment type validation (with automatic mapping)
- Date format validation

### Duplicate Detection
- Employee number conflicts
- Email address conflicts (across User and Employee tables)
- Provides specific error messages with existing employee details

### Transaction Rollback
- Failed user creation rolls back entire row
- Failed employee creation removes created user
- Maintains database integrity

## Security Considerations

1. **Default Passwords**: All users get `password123` initially
2. **Force Password Change**: Recommended to implement password change requirement on first login
3. **Role Assignment**: Users only get 'employee' role, no admin privileges
4. **Company Isolation**: Users are restricted to their company's data

## Usage Notes

1. **Email Optional**: Employees can be created without email addresses
2. **Phone Required**: Phone numbers are required and auto-formatted
3. **Branch Assignment**: Users gain access to specified branch automatically
4. **Role Requirements**: 'employee' role must exist in system for role assignment

## Template Updates

The Excel template now includes:
- 50 diverse sample employees
- Variety of employment types
- Proper enum value examples
- Clear documentation of valid values

This ensures that imported employees can immediately access the system with proper permissions and company context.