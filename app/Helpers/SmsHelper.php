<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SmsHelper
{
    /**
     * Send SMS using configured provider (Kilakona or Beem Africa)
     *
     * @param  string  $phone  Phone number(s) - single or comma-separated
     * @param  string  $message  Message content
     * @return array Response data
     */
    public static function send($phone, $message)
    {
        $phone = preg_replace('/[^0-9+,]/', '', (string) $phone);

        if ($phone === '') {
            $error = 'Invalid phone number provided.';
            Log::error('SMS Error: '.$error);

            return [
                'success' => false,
                'error' => $error,
                'message' => 'Invalid phone number',
                'http_code' => 0,
                'response' => null,
            ];
        }

        if (trim((string) $message) === '') {
            $error = 'Message cannot be empty.';
            Log::error('SMS Error: '.$error);

            return [
                'success' => false,
                'error' => $error,
                'message' => 'Empty message',
                'http_code' => 0,
                'response' => null,
            ];
        }

        $provider = config('services.sms.provider', 'kilakona');

        if ($provider === 'kilakona') {
            return self::sendViaKilakona($phone, $message);
        }

        return self::sendViaBeem($phone, $message);
    }

    /**
     * Check if SMS is properly configured for the active provider.
     */
    public static function isConfigured(): bool
    {
        $provider = config('services.sms.provider', 'kilakona');

        if ($provider === 'kilakona') {
            return filled(config('services.sms.senderid'))
                && filled(config('services.sms.api_key'))
                && filled(config('services.sms.api_secret'))
                && filled(config('services.sms.url'));
        }

        return filled(config('services.sms.senderid'))
            && filled(config('services.sms.token'))
            && filled(config('services.sms.key'))
            && filled(config('services.sms.url'));
    }

    /**
     * Test SMS configuration by sending a test message.
     */
    public static function test(string $testPhone): array
    {
        if (! self::isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMS is not properly configured. Please configure all SMS settings.',
                'error' => 'SMS configuration missing',
            ];
        }

        $testMessage = 'Test SMS from SmartFinance system. If you receive this, your SMS configuration is working correctly.';

        return self::send($testPhone, $testMessage);
    }

    /**
     * Send SMS via Kilakona API
     */
    protected static function sendViaKilakona($phone, $message)
    {
        $senderId = trim((string) config('services.sms.senderid'));
        $apiKey = trim((string) config('services.sms.api_key'));
        $apiSecret = trim((string) config('services.sms.api_secret'));
        $url = trim((string) config('services.sms.url'));
        $callbackUrl = config('services.sms.callback_url');

        if ($senderId === '' || $apiKey === '' || $apiSecret === '' || $url === '') {
            $error = 'Kilakona SMS is not properly configured. Please set sender ID, API key, API secret, and URL.';
            Log::error('SMS sending failed (Kilakona) - Missing config', [
                'senderid' => $senderId,
                'api_key' => $apiKey ? 'set' : 'missing',
                'api_secret' => $apiSecret ? 'set' : 'missing',
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0,
                'response' => null,
            ];
        }

        $data = [
            'senderId' => $senderId,
            'messageType' => 'text',
            'message' => $message,
            'contacts' => $phone,
        ];

        if ($callbackUrl) {
            $data['deliveryReportUrl'] = $callbackUrl;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api_key: '.$apiKey,
            'api_secret: '.$apiSecret,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            Log::error('SMS sending failed (Kilakona) - cURL Error', [
                'error' => $error,
                'phone' => $phone,
                'message' => $message,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0,
                'response' => null,
            ];
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            Log::info('SMS sent successfully (Kilakona)', [
                'phone' => $phone,
                'http_code' => $httpCode,
                'response' => $responseData,
            ]);
        } else {
            Log::error('SMS sending failed (Kilakona) - API Error', [
                'phone' => $phone,
                'http_code' => $httpCode,
                'raw_response' => $response,
                'response' => $responseData,
                'message' => $message,
            ]);
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => $responseData,
            'raw_response' => $response,
            'error' => $httpCode >= 400 ? ($responseData['message'] ?? 'API request failed') : null,
        ];
    }

    /**
     * Send SMS via Beem Africa API (legacy support)
     */
    protected static function sendViaBeem($phone, $message)
    {
        $sid = trim((string) config('services.sms.senderid'));
        $token = trim((string) config('services.sms.token'));
        $key = trim((string) config('services.sms.key'));
        $url = trim((string) config('services.sms.url', 'https://apisms.beem.africa/v1/send'));

        if ($sid === '' || $token === '' || $key === '' || $url === '') {
            $error = 'Beem SMS is not properly configured. Please set sender ID, API key, secret key, and URL.';
            Log::error('SMS sending failed (Beem) - Missing config');

            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0,
                'response' => null,
            ];
        }

        $postData = [
            'source_addr' => $sid,
            'encoding' => 0,
            'schedule_time' => '',
            'message' => $message,
            'recipients' => [
                [
                    'recipient_id' => '1',
                    'dest_addr' => $phone,
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization:Basic '.base64_encode("$key:$token"),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($postData),
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            Log::error('SMS sending failed (Beem)', [
                'error' => $error,
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'response' => null,
            ];
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            Log::info('SMS sent (Beem)', [
                'phone' => $phone,
                'http_code' => $httpCode,
                'response' => $responseData,
            ]);
        } else {
            Log::error('SMS sending failed (Beem) - API Error', [
                'phone' => $phone,
                'http_code' => $httpCode,
                'response' => $responseData,
            ]);
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => $responseData,
            'error' => $httpCode >= 400 ? ($responseData['message'] ?? 'API request failed') : null,
        ];
    }
}
