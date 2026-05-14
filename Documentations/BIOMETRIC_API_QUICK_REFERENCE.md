# Biometric API Quick Reference

## Base URL
```
{{ base_url }}/api/biometric
```

## Authentication
All API endpoints require authentication via headers:
```
X-API-Key: [Your API Key]
X-API-Secret: [Your API Secret]
```

## Endpoints

### 1. Receive Single Punch
**POST** `/api/biometric/punch`

**Request:**
```json
{
    "user_id": "123",
    "punch_time": "2024-12-25 08:30:00",
    "punch_type": "check_in",
    "punch_mode": "fingerprint"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Punch data received",
    "log_id": 123,
    "processed": true
}
```

### 2. Receive Bulk Punches
**POST** `/api/biometric/punches`

**Request:**
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

**Response:**
```json
{
    "success": true,
    "message": "Bulk punches received",
    "processed": 2,
    "failed": 0,
    "total": 2
}
```

### 3. Get Device Status
**GET** `/api/biometric/status`

**Response:**
```json
{
    "success": true,
    "device": {
        "device_code": "DEVICE-001",
        "device_name": "Main Entrance",
        "is_active": true,
        "last_sync_at": "2024-12-25T10:30:00Z",
        "timezone": "Africa/Dar_es_Salaam"
    }
}
```

## Punch Types
- `check_in` - Employee clock in
- `check_out` - Employee clock out
- `break_in` - Break start
- `break_out` - Break end

## Punch Modes
- `fingerprint` - Fingerprint scan
- `face` - Face recognition
- `card` - Card swipe
- `palm` - Palm print
- `password` - Password entry

## cURL Examples

### Single Punch
```bash
curl -X POST https://yourdomain.com/api/biometric/punch \
  -H "X-API-Key: your_api_key" \
  -H "X-API-Secret: your_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "123",
    "punch_time": "2024-12-25 08:30:00",
    "punch_type": "check_in",
    "punch_mode": "fingerprint"
  }'
```

### Bulk Punches
```bash
curl -X POST https://yourdomain.com/api/biometric/punches \
  -H "X-API-Key: your_api_key" \
  -H "X-API-Secret: your_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "punches": [
      {
        "user_id": "123",
        "punch_time": "2024-12-25 08:30:00",
        "punch_type": "check_in",
        "punch_mode": "fingerprint"
      }
    ]
  }'
```

## Error Responses

### Authentication Failed
```json
{
    "success": false,
    "message": "Invalid device credentials"
}
```
**Status Code:** 401

### Validation Error
```json
{
    "success": false,
    "message": "The user_id field is required."
}
```
**Status Code:** 422

### Server Error
```json
{
    "success": false,
    "message": "Error processing punch data: [error details]"
}
```
**Status Code:** 500

## Notes

1. **Timezone**: Punch times are converted to device timezone automatically
2. **Processing**: Logs are processed automatically via scheduled tasks
3. **Duplicates**: System automatically detects and ignores duplicate punches
4. **Mapping**: Employee must be mapped to device before processing
5. **Rate Limiting**: API endpoints are rate-limited for security

