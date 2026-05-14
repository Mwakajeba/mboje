# Biometric Device Integration Guide

## Overview

This guide explains how to integrate biometric devices (fingerprint, face recognition, card readers, etc.) with the HR & Payroll attendance system.

## Architecture

### Components

1. **BiometricDevice Model**: Stores device configuration and credentials
2. **BiometricLog Model**: Stores raw punch data from devices
3. **BiometricEmployeeMapping**: Maps device user IDs to system employees
4. **BiometricService**: Processes logs and creates attendance records
5. **BiometricApiController**: API endpoints for devices to send data
6. **BiometricDeviceController**: Web interface for device management

## Setup Process

### Step 1: Register Biometric Device

1. Navigate to **HR & Payroll → Biometric Devices**
2. Click **Add Device**
3. Fill in device information:
   - **Device Code**: Unique identifier (e.g., DEVICE-001)
   - **Device Name**: Descriptive name
   - **Device Type**: Fingerprint, Face, Card, or Palm
   - **Connection Type**: API, TCP/IP, UDP, or File Import
   - **IP Address & Port**: For TCP/UDP connections
   - **Timezone**: Device timezone (default: Africa/Dar_es_Salaam)
   - **Sync Interval**: How often to sync (minutes)

4. Save the device - API credentials will be auto-generated

### Step 2: Get API Credentials

1. Open the device details page
2. Copy the **API Key** and **API Secret**
3. These credentials are used to authenticate device API calls

### Step 3: Map Employees to Device

1. On the device details page, click **Map Employee**
2. Select the employee
3. Enter the **Device User ID** (the user ID stored in the biometric device)
4. Optionally enter **Device User Name**
5. Save the mapping

**Important**: Each employee must be mapped to the device with their device user ID before attendance can be processed.

### Step 4: Configure Device to Send Data

Configure your biometric device to send punch data to the API endpoint:

**API Endpoint**: `POST {{ base_url }}/api/biometric/punch`

**Headers**:
```
X-API-Key: [Your API Key]
X-API-Secret: [Your API Secret]
Content-Type: application/json
```

**Request Body**:
```json
{
    "user_id": "123",
    "punch_time": "2024-12-25 08:30:00",
    "punch_type": "check_in",
    "punch_mode": "fingerprint"
}
```

**Punch Types**:
- `check_in`: Clock in
- `check_out`: Clock out
- `break_in`: Break start
- `break_out`: Break end

**Response**:
```json
{
    "success": true,
    "message": "Punch data received",
    "log_id": 123,
    "processed": true
}
```

## Bulk Data Upload

For devices that send multiple punches at once:

**Endpoint**: `POST {{ base_url }}/api/biometric/punches`

**Request Body**:
```json
{
    "punches": [
        {
            "user_id": "123",
            "punch_time": "2024-12-25 08:30:00",
            "punch_type": "check_in",
            "punch_mode": "fingerprint"
        },
        {
            "user_id": "124",
            "punch_time": "2024-12-25 08:35:00",
            "punch_type": "check_in",
            "punch_mode": "fingerprint"
        }
    ]
}
```

## Processing Flow

1. **Device sends punch data** → API receives and creates `BiometricLog` record
2. **BiometricService processes log**:
   - Finds employee mapping using `device_user_id`
   - Gets or creates attendance record for the date
   - Updates clock in/out times
   - Calculates hours, overtime, exceptions
   - Links log to attendance record
3. **Attendance record** is ready for payroll processing

## Automatic Processing

### Scheduled Tasks

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync biometric devices every 15 minutes
    $schedule->command('biometric:sync')->everyFifteenMinutes();
    
    // Process pending logs every 5 minutes
    $schedule->command('biometric:process-logs')->everyFiveMinutes();
}
```

### Manual Processing

**Sync Device**:
```bash
php artisan biometric:sync --device-id=1
```

**Process Logs**:
```bash
php artisan biometric:process-logs --device-id=1 --limit=100
```

## Device Status

Check device sync status on the device details page:
- **Last Sync**: When device was last synced
- **Sync Status**: Whether sync is due
- **Pending Logs**: Number of logs waiting to be processed
- **Failed Logs**: Logs that failed processing (check error messages)

## Troubleshooting

### Logs Not Processing

1. **Check Employee Mapping**: Ensure employee is mapped to device with correct `device_user_id`
2. **Check Log Status**: View logs on device details page
3. **Check Error Messages**: Failed logs show error messages
4. **Verify Timezone**: Ensure device timezone matches system timezone

### API Authentication Fails

1. **Verify API Key/Secret**: Check device details page
2. **Regenerate Credentials**: Use "Regenerate API Key" if needed
3. **Check Headers**: Ensure `X-API-Key` and `X-API-Secret` headers are sent

### Duplicate Logs

The system automatically detects and marks duplicate logs. Check the log status on the device details page.

## Supported Device Types

- **Fingerprint**: Standard fingerprint scanners
- **Face Recognition**: Face recognition devices
- **Card Readers**: RFID/NFC card readers
- **Palm Print**: Palm print scanners

## Connection Types

- **API (REST)**: Devices send data via HTTP POST (recommended)
- **TCP/IP**: Direct TCP connection (requires IP/Port)
- **UDP**: UDP connection (requires IP/Port)
- **File Import**: Import data from CSV/Excel files (manual)

## Security

- API credentials are encrypted and stored securely
- API endpoints validate device credentials before accepting data
- All API calls are logged for audit purposes
- Failed authentication attempts are logged

## Integration Examples

### Example: ZKTeco Device

```php
// Device sends data via HTTP POST
$response = Http::withHeaders([
    'X-API-Key' => $apiKey,
    'X-API-Secret' => $apiSecret,
])->post('https://yourdomain.com/api/biometric/punch', [
    'user_id' => $deviceUserId,
    'punch_time' => now()->format('Y-m-d H:i:s'),
    'punch_type' => 'check_in',
    'punch_mode' => 'fingerprint',
]);
```

### Example: File Import

For devices that export data to files:

1. Export data from device to CSV/Excel
2. Format: `user_id, punch_time, punch_type, punch_mode`
3. Use bulk API endpoint or import via web interface

## Best Practices

1. **Map employees immediately** after device setup
2. **Test with sample data** before going live
3. **Monitor sync status** regularly
4. **Process failed logs** manually if needed
5. **Keep API credentials secure** - regenerate if compromised
6. **Set appropriate sync intervals** based on device capabilities
7. **Use timezone correctly** - ensure device and system timezones match

## Support

For device-specific integration help:
1. Check device manufacturer documentation
2. Verify API endpoint accessibility
3. Test with curl/Postman first
4. Check application logs for errors
5. Review biometric logs table for processing status

