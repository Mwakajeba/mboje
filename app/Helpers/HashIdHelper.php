<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class HashIdHelper
{
    /**
     * Encode an ID to a hash
     */
    public static function encode($id)
    {
        $salt = 'smartfinance_payment_2024';
        return base64_encode($id . '_' . md5($salt . $id));
    }

    /**
     * Decode a hash to an ID
     */
    public static function decode($hash)
    {
        try {
            $decoded = base64_decode($hash);
            if ($decoded === false) {
                return null;
            }
            
            $parts = explode('_', $decoded);
            if (count($parts) !== 2) {
                return null;
            }
            
            $id = (int) $parts[0];
            $expectedHash = md5('smartfinance_payment_2024' . $id);
            
            // Validate the hash
            if ($parts[1] !== $expectedHash) {
                return null;
            }
            
            return $id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a hash is valid
     */
    public static function isValid($hash)
    {
        return self::decode($hash) !== null;
    }
} 