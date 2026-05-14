# Login Troubleshooting Guide

## Phone Number Format Issues

If you're experiencing "Invalid phone number or password" errors, the issue is likely related to phone number format.

### How It Works

The mobile app now automatically normalizes phone numbers before sending them to the backend. The normalization process:

1. **Cleans** the phone number (removes spaces, dashes, etc.)
2. **Normalizes** to standard format: `255xxxxxxxxx` (12 digits)
3. **Tries** the normalized format first
4. **Falls back** to original format if normalized format fails

### Supported Input Formats

You can enter your phone number in any of these formats:
- `0712345678` (starts with 0)
- `255712345678` (starts with 255)
- `+255712345678` (with + prefix)
- `712345678` (9 digits only)

All formats will be automatically converted to `255712345678` before sending to the backend.

### Backend Phone Matching

The backend's `find_guardian_by_phone` function tries multiple variations:
- `255xxxxxxxxx` (normalized format)
- `0xxxxxxxxx` (local format)
- `+255xxxxxxxxx` (international format)
- `xxxxxxxxx` (9 digits)

So even if your phone is stored in the database as `0712345678`, the backend will find it when you send `255712345678`.

### Common Issues

1. **"Invalid phone number or password"**
   - Check that you're using the correct phone number (the one registered in the system)
   - Check that your password is correct
   - Try entering the phone number in a different format (e.g., if you tried `0712345678`, try `255712345678`)

2. **"Account is temporarily locked"**
   - Too many failed login attempts
   - Wait a few minutes and try again

3. **"Access denied"**
   - Your account might not be set up for parent access
   - Contact support to enable parent access

### Testing

To test if phone normalization is working:

1. Enter your phone number in format: `0712345678`
2. The app will automatically convert it to: `255712345678`
3. This normalized format is sent to the backend
4. Backend will find your account regardless of how it's stored in the database

### Debug Information

If login still fails after trying different formats, check:
- The phone number format stored in the `guardians` table in the database
- Whether the password is correctly hashed
- Whether the guardian account has `password` field set

