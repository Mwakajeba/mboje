/// Phone Number Helper
/// 
/// Normalizes phone numbers to match the backend format
/// Handles Tanzania phone numbers: +255, 0, and 255 prefixes

class PhoneHelper {
  /// Normalize phone number to standard format (255xxxxxxxxx)
  /// 
  /// Handles:
  /// - +255xxxxxxxxx -> 255xxxxxxxxx
  /// - 0xxxxxxxxx -> 255xxxxxxxxx
  /// - 255xxxxxxxxx -> 255xxxxxxxxx (already correct)
  /// - xxxxxxxxx (9 digits) -> 255xxxxxxxxx
  static String normalizePhoneNumber(String phone) {
    // Remove all non-digit characters except +
    String cleaned = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    
    // If it starts with +255, convert to 255
    if (cleaned.startsWith('+255')) {
      return '255${cleaned.substring(4)}';
    }
    
    // If it starts with 0, convert to 255
    if (cleaned.startsWith('0')) {
      return '255${cleaned.substring(1)}';
    }
    
    // If it starts with 255, return as is
    if (cleaned.startsWith('255')) {
      return cleaned;
    }
    
    // If it's a 9-digit number (Tanzania mobile), add 255 prefix
    if (cleaned.length == 9) {
      return '255$cleaned';
    }
    
    // Return as is if it doesn't match any pattern
    return cleaned;
  }
  
  /// Format phone number for display
  static String formatPhoneForDisplay(String phone) {
    String normalized = normalizePhoneNumber(phone);
    
    // If it's a Tanzania number (starts with 255 and has 12 digits)
    if (normalized.startsWith('255') && normalized.length == 12) {
      String number = normalized.substring(3); // Remove 255 prefix
      return '+255 ${number.substring(0, 2)} ${number.substring(2, 5)} ${number.substring(5)}';
    }
    
    return phone;
  }
  
  /// Validate Tanzania phone number
  static bool validateTanzaniaPhone(String phone) {
    String normalized = normalizePhoneNumber(phone);
    
    // Tanzania mobile numbers should be 12 digits starting with 255
    return normalized.length == 12 && normalized.startsWith('255');
  }
  
  /// Clean phone number (remove spaces, dashes, etc.)
  static String cleanPhoneNumber(String phone) {
    return phone.replaceAll(RegExp(r'[^0-9+]'), '');
  }
}

