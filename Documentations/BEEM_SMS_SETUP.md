# Beem SMS Configuration Setup

## Environment Variables

Add the following variables to your `.env` file:

```env
# Beem SMS Configuration
BEEM_API_KEY=your_beem_api_key_here
BEEM_SECRET_KEY=your_beem_secret_key_here
BEEM_SENDER_ID=SAFCO
```

## Configuration Details

- **BEEM_API_KEY**: Your Beem API key from your Beem account
- **BEEM_SECRET_KEY**: Your Beem secret key from your Beem account  
- **BEEM_SENDER_ID**: The sender ID that will appear on SMS messages (defaults to "SAFCO")

## How it Works

1. When creating a new customer, check the "Send Welcome SMS" checkbox
2. The system will automatically send a welcome SMS to the customer's phone number
3. The SMS message will include:
   - Customer's name
   - Customer number
   - Welcome message in Swahili

## SMS Message Format

The welcome SMS will be sent in this format:
```
Karibu [Customer Name]! Umesajiliwa kwenye mfumo wetu. Nambari yako ya mteja ni: [Customer Number]. Asante!
```

## Error Handling

- If SMS sending fails, the error will be logged but won't prevent customer creation
- SMS errors are logged in the Laravel log files
- The system will continue with customer creation even if SMS fails

## Requirements

- Valid Beem account with API credentials
- Internet connection for API calls
- Valid phone number format for the customer 