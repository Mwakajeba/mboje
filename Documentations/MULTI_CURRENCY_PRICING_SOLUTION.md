# Multi-Currency Item Pricing Solution

## Problem Statement
When creating an invoice in a foreign currency (e.g., USD), the system uses the item's selling price (stored in TZS) directly as if it were in the invoice currency. This results in incorrect pricing.

**Example:**
- Item selling price: 10,000 TZS
- Invoice currency: USD
- Exchange rate: 1 USD = 2,500 TZS
- **Current (Wrong):** System uses 10,000 as USD
- **Expected:** 10,000 TZS ÷ 2,500 = 4 USD

## Solution Approach

### Phase 1: Automatic Conversion (Recommended - Quick Fix)
**Implementation:**
1. When item is selected, check invoice currency
2. If currency ≠ functional currency, convert price: `Price in FCY = Price in TZS ÷ Exchange Rate`
3. Display converted price with indicator showing original TZS price
4. Allow manual override if needed

**Pros:**
- Quick to implement
- Works immediately
- No database changes needed
- User can still override

**Cons:**
- May not reflect actual market prices in different currencies
- Conversion might not match real-world pricing strategies

### Phase 2: Currency-Specific Prices (Future Enhancement)
**Implementation:**
1. Create `item_prices` table:
   - `item_id`, `currency_code`, `unit_price`, `effective_date`, `company_id`
2. Allow setting prices per currency per item
3. When item selected, use currency-specific price if available, else convert base price

**Pros:**
- Most accurate pricing
- Reflects real market conditions
- Professional solution

**Cons:**
- More complex
- Requires database changes
- More maintenance

## Recommended Implementation

**Start with Phase 1 (Automatic Conversion)**, then enhance to Phase 2 if needed.

### How Other Systems Handle This:

1. **SAP/Oracle (Enterprise):**
   - Currency-specific price lists
   - Multiple price lists per currency
   - Price agreements by customer/currency

2. **QuickBooks/Xero (SMB):**
   - Base price in home currency
   - Auto-convert using exchange rate
   - Manual override allowed

3. **Odoo:**
   - Multi-currency price lists
   - Automatic conversion with override option

4. **Microsoft Dynamics:**
   - Price lists by currency
   - Automatic conversion fallback

## Implementation Details

### JavaScript Logic:
```javascript
// When currency changes or item is selected
function convertItemPrice(basePrice, fromCurrency, toCurrency, exchangeRate) {
    if (fromCurrency === toCurrency) {
        return basePrice;
    }
    // Convert from functional currency (TZS) to foreign currency
    return basePrice / exchangeRate;
}

// Example: 10,000 TZS to USD at rate 2,500
// Result: 10,000 / 2,500 = 4 USD
```

### UI Enhancement:
- Show: "Price: 4.00 USD (10,000.00 TZS)"
- Allow manual edit
- Re-convert if exchange rate changes

