<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class LipishaService
{
    /**
     * Check if LIPISHA integration is enabled
     */
    public static function isEnabled()
    {
        $enabled = env('LIPISHA_ENABLED', SystemSetting::getValue('lipisha_enabled', false));
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Generate LIPISHA token (cached for 1 hour to avoid regenerating)
     * According to LIPISHA API documentation:
     * Endpoint: POST /v1/api/auth/generate-token
     * Response: {"status": "success", "data": {"access_token": "...", "refresh_token": "..."}}
     */
    public static function generateToken()
    {
        try {
            // Check if LIPISHA is enabled
            if (!self::isEnabled()) {
                Log::info('LIPISHA integration is disabled');
                return null;
            }

            // Check cache first (token valid for 1 hour)
            $cacheKey = 'lipisha_token';
            $cachedToken = \Cache::get($cacheKey);
            
            if ($cachedToken) {
                Log::info('LIPISHA token retrieved from cache');
                return $cachedToken;
            }

            $businessId = env('LIPISHA_BUSINESS_ID', SystemSetting::getValue('lipisha_business_id', ''));
            $businessName = env('LIPISHA_BUSINESS_NAME', SystemSetting::getValue('lipisha_business_name', ''));
            $businessKey = env('LIPISHA_BUSINESS_KEY', SystemSetting::getValue('lipisha_business_key', ''));

            if (empty($businessId) || empty($businessName) || empty($businessKey)) {
                Log::error('LIPISHA credentials not configured');
                return null;
            }

            $url = "https://lipisha.co/v1/api/auth/generate-token";

            $postData = [
                "business_id" => $businessId,
                "business_name" => $businessName,
                "business_key" => $businessKey
            ];

            $headers = [
                "Content-Type: application/json"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('LIPISHA token generation error: ' . $curlError);
                return null;
            }

            if ($httpCode !== 200) {
                Log::error('LIPISHA token generation HTTP error: ' . $httpCode);
                return null;
            }

            $result = json_decode($response, true);

            // Check various success indicators - PRIORITY: data.access_token (as per API response)
            $token = null;
            
            // PRIORITY 1: Check data.access_token (most common in logs)
            if (isset($result['data']['access_token']) && !empty($result['data']['access_token'])) {
                $token = $result['data']['access_token'];
                Log::info('✅ LIPISHA token found in data.access_token');
            }
            // PRIORITY 2: Check data.token
            elseif (isset($result['data']['token']) && !empty($result['data']['token'])) {
                $token = $result['data']['token'];
                Log::info('✅ LIPISHA token found in data.token');
            }
            // PRIORITY 3: Check access_token at root
            elseif (isset($result['access_token']) && !empty($result['access_token'])) {
                $token = $result['access_token'];
                Log::info('✅ LIPISHA token found in access_token');
            }
            // PRIORITY 4: Check token at root
            elseif (isset($result['token']) && !empty($result['token'])) {
                $token = $result['token'];
                Log::info('✅ LIPISHA token found in token');
            }
            // PRIORITY 5: Check if status is success and try again
            elseif (isset($result['status']) && ($result['status'] == 'SUCCESS' || $result['status'] == 'success')) {
                $token = $result['data']['access_token'] 
                    ?? $result['data']['token'] 
                    ?? $result['access_token'] 
                    ?? $result['token'] 
                    ?? null;
                if ($token) {
                    Log::info('✅ LIPISHA token found with status check');
                }
            }

            if ($token) {
                // Cache token for 1 hour (3600 seconds)
                \Cache::put($cacheKey, $token, 3600);
                Log::info('LIPISHA token generated and cached', ['token_length' => strlen($token)]);
                return $token;
            }

            Log::error('LIPISHA token not found in response', [
                'response' => $result,
                'response_keys' => is_array($result) ? array_keys($result) : 'not_array',
                'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : 'not_array'
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('LIPISHA token generation exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create LIPISHA customer
     * According to LIPISHA API documentation:
     * Endpoint: POST /v1/api/customers/create-customer
     * Response: {"status": "success", "data": {"customer_id": <integer>, ...}}
     */
    public static function createCustomer($name, $phoneNumber = null, $email = null, $tin = null, $vrn = null, $contactPerson = null, $contactNumber = null)
    {
        // Check if LIPISHA is enabled
        if (!self::isEnabled()) {
            Log::info('LIPISHA integration is disabled - skipping customer creation');
            return [
                'success' => false,
                'message' => 'LIPISHA integration is disabled'
            ];
        }

        $maxRetries = 5; // Increased retries for better reliability
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                $token = self::generateToken();
                
                if (!$token) {
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        Log::warning('Failed to generate LIPISHA token, retrying...', [
                            'retry' => $retryCount,
                            'max_retries' => $maxRetries
                        ]);
                        usleep(500000); // Wait 500ms before retry
                        continue;
                    }
                    
                    Log::error('Failed to generate LIPISHA token for customer creation after retries');
                    return [
                        'success' => false,
                        'message' => 'Failed to authenticate with LIPISHA'
                    ];
                }

            $url = "https://lipisha.co/v1/api/customers/create-customer";

            $data = [
                "name" => $name
            ];

            // Optional fields
            if ($phoneNumber) $data["phone_number"] = $phoneNumber;
            if ($email) $data["email"] = $email;
            if ($tin) $data["tin"] = $tin;
            if ($vrn) $data["vrn"] = $vrn;
            if ($contactPerson) $data["contact_person"] = $contactPerson;
            if ($contactNumber) $data["contact_number"] = $contactNumber;

            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer $token"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90); // Increased timeout for large batches (90 seconds)
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Connection timeout
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3); // Maximum redirects

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    Log::warning('LIPISHA customer creation curl error, retrying...', [
                        'error' => $curlError,
                        'retry' => $retryCount,
                        'max_retries' => $maxRetries
                    ]);
                    usleep(1000000 * $retryCount); // Exponential backoff: 1s, 2s, 3s
                    continue;
                }
                
                Log::error('LIPISHA customer creation curl error after retries: ' . $curlError);
                return [
                    'success' => false,
                    'message' => 'Network error: ' . $curlError
                ];
            }

            // HTTP 200 (OK) and 201 (Created) are both success codes for creation
            if ($httpCode !== 200 && $httpCode !== 201) {
                $retryCount++;
                if ($retryCount < $maxRetries && ($httpCode >= 500 || $httpCode === 429)) {
                    // Retry on server errors (5xx) or rate limit (429)
                    Log::warning('LIPISHA customer creation HTTP error, retrying...', [
                        'http_code' => $httpCode,
                        'retry' => $retryCount,
                        'max_retries' => $maxRetries
                    ]);
                    usleep(1000000 * $retryCount); // Exponential backoff
                    continue;
                }
                
                Log::error('LIPISHA customer creation HTTP error: ' . $httpCode);
                return [
                    'success' => false,
                    'message' => 'API returned HTTP code: ' . $httpCode
                ];
            }

            $result = json_decode($response, true);
            
            // Log full response for debugging (exactly as returned from API)
            Log::info('LIPISHA customer creation response (RAW)', [
                'raw_response' => $response,
                'decoded_response' => $result,
                'response_type' => gettype($result),
                'response_keys' => is_array($result) ? array_keys($result) : 'not_array',
                'http_code' => $httpCode
            ]);

            // Check for success and extract customer_id
            // According to LIPISHA API documentation:
            // Response: {"status": "success", "message": "Customer created successfully", "data": {"customer_id": <integer>, ...}}
            // Note: control_number is NOT returned in customer creation - it comes from billing/payment-reference
            $isSuccess = false;
            $customerId = null;
            $message = '';

            // Extract customer_id from response following LIPISHA API documentation EXACTLY
            // According to docs: {"status": "success", "message": "Customer created successfully", "data": {"customer_id": <integer>, ...}}
            // HTTP 201 means customer was created successfully
            if (is_array($result)) {
                $status = $result['status'] ?? null;
                $message = $result['message'] ?? '';
                
                // According to documentation, customer_id is ALWAYS in data['customer_id'] as integer
                // Check this FIRST and ONLY - this is the documented location
                if (isset($result['data']) && is_array($result['data'])) {
                    // PRIMARY: data['customer_id'] - this is where it SHOULD be according to docs
                    if (isset($result['data']['customer_id'])) {
                        $rawCustomerId = $result['data']['customer_id'];
                        
                        // Convert to integer (as per documentation)
                        // Accept any numeric value (int, string number, etc.)
                        if (is_numeric($rawCustomerId) && (int)$rawCustomerId > 0) {
                            $customerId = (int) $rawCustomerId;
                            $isSuccess = true;
                            $message = $message ?: 'Customer created successfully';
                            
                            Log::info('✅✅✅ LIPISHA customer_id EXTRACTED from data.customer_id (PRIMARY)', [
                                'customer_id' => $customerId,
                                'customer_id_raw' => $rawCustomerId,
                                'customer_id_type' => gettype($rawCustomerId),
                                'customer_id_converted' => gettype($customerId),
                                'status' => $status,
                                'message' => $message,
                                'http_code' => $httpCode,
                                'response_structure' => [
                                    'has_status' => isset($result['status']),
                                    'has_message' => isset($result['message']),
                                    'has_data' => isset($result['data']),
                                    'data_keys' => is_array($result['data']) ? array_keys($result['data']) : 'not_array'
                                ]
                            ]);
                        } else {
                            Log::warning('LIPISHA customer_id exists but is not valid numeric', [
                                'customer_id_raw' => $rawCustomerId,
                                'customer_id_type' => gettype($rawCustomerId),
                                'is_numeric' => is_numeric($rawCustomerId),
                                'is_positive' => is_numeric($rawCustomerId) && (int)$rawCustomerId > 0
                            ]);
                        }
                    } else {
                        // Log why extraction failed - data exists but no customer_id
                        Log::warning('LIPISHA response has data but no customer_id', [
                            'data_keys' => array_keys($result['data']),
                            'data_structure' => $result['data'],
                            'status' => $status,
                            'http_code' => $httpCode
                        ]);
                    }
                } else {
                    // Log why extraction failed - no data key
                    Log::warning('LIPISHA response missing data key', [
                        'response_keys' => array_keys($result),
                        'has_data' => isset($result['data']),
                        'data_type' => isset($result['data']) ? gettype($result['data']) : 'NOT_SET',
                        'status' => $status,
                        'http_code' => $httpCode,
                        'full_response' => $result
                    ]);
                }
            } else {
                Log::error('LIPISHA response is not an array', [
                    'response_type' => gettype($result),
                    'response' => $result,
                    'http_code' => $httpCode
                ]);
            }
            
            // Log extraction result
            if ($isSuccess && $customerId) {
                Log::info('LIPISHA customer_id extracted successfully', [
                    'customer_id' => $customerId,
                    'customer_id_type' => gettype($customerId),
                    'http_code' => $httpCode
                ]);
            } else {
                Log::warning('LIPISHA customer creation - unable to extract customer_id', [
                    'response_structure' => $result,
                    'response_keys' => is_array($result) ? array_keys($result) : 'not_array',
                    'http_code' => $httpCode,
                    'is_success' => $isSuccess,
                    'customer_id_found' => !empty($customerId),
                    'customer_id_value' => $customerId
                ]);
            }

            // Return customer_id if found (as integer, convert to string for storage)
            if ($isSuccess && $customerId) {
                Log::info('✅ LIPISHA customer_id EXTRACTED SUCCESSFULLY', [
                    'customer_id' => $customerId,
                    'type' => gettype($customerId)
                ]);
                
                // Convert to string for database storage (customer_id is integer in API but stored as string)
                $customerIdString = (string) $customerId;
                
                // Break out of retry loop on success
                return [
                    'success' => true,
                    'customer_id' => $customerIdString,
                    'message' => $message,
                    'data' => $result
                ];
            } else {
                $errorMessage = isset($result['message']) 
                    ? $result['message'] 
                    : (isset($result['error']) ? $result['error'] : 'Unknown error');
                
                // Log the full response for debugging
                Log::error('❌ LIPISHA customer creation FAILED - no customer_id extracted', [
                    'response' => $result,
                    'is_success' => $isSuccess,
                    'customer_id_found' => !empty($customerId),
                    'customer_id_value' => $customerId,
                    'error_message' => $errorMessage,
                    'response_keys' => is_array($result) ? array_keys($result) : 'not_array'
                ]);
                    
                // If customer_id not found, retry if we have retries left
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    Log::warning('LIPISHA customer creation - no customer_id, retrying...', [
                        'retry' => $retryCount,
                        'max_retries' => $maxRetries,
                        'response' => $result
                    ]);
                    usleep(1000000 * $retryCount); // Exponential backoff
                    continue;
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to create customer: ' . $errorMessage,
                    'data' => $result,
                    'customer_id' => null // Explicitly set to null
                ];
            }
            
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    Log::warning('LIPISHA customer creation exception, retrying...', [
                        'error' => $e->getMessage(),
                        'retry' => $retryCount,
                        'max_retries' => $maxRetries
                    ]);
                    usleep(1000000 * $retryCount); // Exponential backoff
                    continue;
                }
                
                Log::error('LIPISHA customer creation exception after retries: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        // If we get here, all retries failed
        return [
            'success' => false,
            'message' => 'Failed after ' . $maxRetries . ' retries'
        ];
    }

    /**
     * Get or create LIPISHA customer for a student
     * Returns customer_id which is used for invoice control numbers
     */
    public static function getOrCreateCustomerForStudent($student, $phoneNumber = null, $email = null)
    {
        // Check if LIPISHA is enabled
        if (!self::isEnabled()) {
            Log::info('LIPISHA integration is disabled - skipping customer creation for student', [
                'student_id' => $student->id
            ]);
            return [
                'success' => false,
                'message' => 'LIPISHA integration is disabled'
            ];
        }

        // Check database directly with lock to avoid race conditions
        $existingCustomerId = \DB::transaction(function () use ($student) {
            return \DB::table('students')
                ->where('id', $student->id)
                ->lockForUpdate()
                ->value('lipisha_customer_id');
        }, 5);
        
        // Only use existing if it's valid (not empty, not '0', not null)
        if (!empty($existingCustomerId) && 
            trim($existingCustomerId) !== '' && 
            trim($existingCustomerId) !== '0' &&
            strlen(trim($existingCustomerId)) > 0) {
            Log::info('LIPISHA: Using existing customer_id from database', [
                'student_id' => $student->id,
                'customer_id' => $existingCustomerId
            ]);
            return [
                'success' => true,
                'customer_id' => trim($existingCustomerId),
                'message' => 'Using existing customer ID'
            ];
        }
        
        // Log that we're creating new customer
        Log::info('LIPISHA: Creating new customer for student', [
            'student_id' => $student->id,
            'existing_customer_id' => $existingCustomerId,
            'is_empty' => empty($existingCustomerId)
        ]);

        // Create customer name from student name
        $name = trim($student->first_name . ' ' . ($student->last_name ?? ''));
        if (empty($name)) {
            $name = $student->admission_number ?? 'Student ' . $student->id;
        }

        // Use student email or phone if available
        $studentEmail = $email ?? $student->email ?? null;
        $studentPhone = $phoneNumber ?? null;

        // Try to get phone from guardian if available
        if (!$studentPhone && $student->guardians && $student->guardians->isNotEmpty()) {
            $guardian = $student->guardians->first();
            $studentPhone = $guardian->phone ?? null;
        }

        // Create customer in LIPISHA
        $result = self::createCustomer(
            $name,
            $studentPhone,
            $studentEmail
        );

        // Log the result before processing
        Log::info('LIPISHA getOrCreateCustomerForStudent result', [
            'student_id' => $student->id,
            'success' => $result['success'] ?? false,
            'has_customer_id' => isset($result['customer_id']),
            'customer_id' => $result['customer_id'] ?? null,
            'customer_id_empty' => empty($result['customer_id'] ?? null),
            'result_keys' => array_keys($result ?? []),
            'message' => $result['message'] ?? 'no_message'
        ]);

        // CRITICAL: Extract customer_id from result
        // createCustomer() returns: ['success' => true, 'customer_id' => '<string>', 'message' => '...', 'data' => {...}]
        // So customer_id is ALREADY extracted and converted to string in result['customer_id']
        $customerIdToSave = null;
        
        // PRIORITY 1: Check result['customer_id'] (direct from createCustomer() return - this is where it SHOULD be)
        if (isset($result['customer_id']) && 
            $result['customer_id'] !== null && 
            $result['customer_id'] !== '' &&
            $result['customer_id'] !== '0' &&
            trim((string)$result['customer_id']) !== '') {
            $customerIdToSave = trim((string)$result['customer_id']);
            Log::info('✅✅✅ LIPISHA: Found customer_id in result[\'customer_id\'] (PRIMARY)', [
                'student_id' => $student->id,
                'customer_id' => $customerIdToSave,
                'customer_id_raw' => $result['customer_id'],
                'customer_id_type' => gettype($result['customer_id'])
            ]);
        }
        // PRIORITY 2: Check result['data']['customer_id'] (fallback - from raw API response)
        elseif (isset($result['data']) && is_array($result['data']) && 
                isset($result['data']['customer_id']) && 
                $result['data']['customer_id'] !== null && 
                $result['data']['customer_id'] !== '' &&
                $result['data']['customer_id'] !== '0' &&
                is_numeric($result['data']['customer_id']) &&
                (int)$result['data']['customer_id'] > 0) {
            $customerIdToSave = (string)(int)$result['data']['customer_id']; // Convert to int then string
            Log::info('✅ LIPISHA: Found customer_id in result[\'data\'][\'customer_id\'] (FALLBACK)', [
                'student_id' => $student->id,
                'customer_id' => $customerIdToSave,
                'customer_id_raw' => $result['data']['customer_id'],
                'customer_id_type' => gettype($result['data']['customer_id'])
            ]);
        }
        // PRIORITY 3: Check result['data']['id'] (last resort fallback)
        elseif (isset($result['data']) && is_array($result['data']) && 
                isset($result['data']['id']) && 
                $result['data']['id'] !== null && 
                $result['data']['id'] !== '' &&
                $result['data']['id'] !== '0' &&
                is_numeric($result['data']['id']) &&
                (int)$result['data']['id'] > 0) {
            $customerIdToSave = (string)(int)$result['data']['id'];
            Log::info('✅ LIPISHA: Found customer_id in result[\'data\'][\'id\'] (LAST RESORT)', [
                'student_id' => $student->id,
                'customer_id' => $customerIdToSave
            ]);
        } else {
            // Log why extraction failed
            Log::error('❌❌❌ LIPISHA: customer_id NOT FOUND in result', [
                'student_id' => $student->id,
                'result_success' => $result['success'] ?? false,
                'result_keys' => array_keys($result ?? []),
                'has_customer_id' => isset($result['customer_id']),
                'customer_id_value' => $result['customer_id'] ?? 'NOT_SET',
                'has_data' => isset($result['data']),
                'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : 'not_array',
                'data_customer_id' => $result['data']['customer_id'] ?? 'NOT_SET',
                'full_result' => $result
            ]);
        }
        
        // Log what we're trying to save
        Log::info('🔍 LIPISHA EXTRACTING customer_id', [
            'student_id' => $student->id,
            'result_success' => $result['success'] ?? false,
            'result_customer_id' => $result['customer_id'] ?? 'NOT_SET',
            'data_customer_id' => $result['data']['customer_id'] ?? 'NOT_SET',
            'data_id' => $result['data']['id'] ?? 'NOT_SET',
            'customer_id_extracted' => $customerIdToSave,
            'customer_id_empty' => empty($customerIdToSave),
            'result_keys' => array_keys($result ?? []),
            'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : 'not_array'
        ]);
        
        if ($result['success'] && !empty($customerIdToSave)) {
            // Ensure student is saved and has an ID
            if (!$student->exists || !$student->id) {
                $student->save();
                Log::info('Student saved before updating customer_id', ['student_id' => $student->id]);
            }
            
            // Convert customer_id to string (database expects string)
            $customerIdValue = trim((string) $customerIdToSave);
            
            Log::info('💾 LIPISHA ATTEMPTING TO SAVE', [
                'student_id' => $student->id,
                'customer_id_value' => $customerIdValue,
                'student_exists' => $student->exists,
                'student_has_id' => !empty($student->id)
            ]);
            
            // CRITICAL: Save customer_id - MULTIPLE METHODS TO ENSURE IT SAVES
            // Use transaction with lock to prevent race conditions
            \DB::beginTransaction();
            try {
                // Use lockForUpdate to prevent other jobs from processing the same student
                $existingRecord = \DB::table('students')
                    ->where('id', $student->id)
                    ->lockForUpdate()
                    ->first();
                
                // Double-check: if customer_id already exists, don't overwrite
                if (!empty($existingRecord->lipisha_customer_id) && 
                    trim($existingRecord->lipisha_customer_id) !== '' && 
                    trim($existingRecord->lipisha_customer_id) !== '0') {
                    \DB::commit();
                    Log::info('LIPISHA customer_id already exists, skipping save', [
                        'student_id' => $student->id,
                        'existing_customer_id' => $existingRecord->lipisha_customer_id
                    ]);
                    return [
                        'success' => true,
                        'customer_id' => $existingRecord->lipisha_customer_id,
                        'message' => 'Using existing customer ID'
                    ];
                }
                
                // Method 1: Direct DB update (most reliable) with lock
                $updated1 = \DB::table('students')
                    ->where('id', $student->id)
                    ->where(function($query) {
                        $query->whereNull('lipisha_customer_id')
                              ->orWhere('lipisha_customer_id', '')
                              ->orWhere('lipisha_customer_id', '0');
                    })
                    ->update(['lipisha_customer_id' => $customerIdValue]);
                
                // Method 2: Model update (for Eloquent consistency)
                $student->lipisha_customer_id = $customerIdValue;
                $saved2 = $student->save();
                
                // Commit transaction
                \DB::commit();
                
                // Method 3: Raw SQL as final verification (outside transaction)
                $updated3 = \DB::statement(
                    "UPDATE students SET lipisha_customer_id = ? WHERE id = ? AND (lipisha_customer_id IS NULL OR lipisha_customer_id = '' OR lipisha_customer_id = '0')",
                    [$customerIdValue, $student->id]
                );
                
                // Refresh and verify multiple times
                $verificationRetries = 3;
                $verificationCount = 0;
                $dbValue = null;
                
                while ($verificationCount < $verificationRetries) {
                    $student->refresh();
                    $dbValue = \DB::table('students')
                        ->where('id', $student->id)
                        ->value('lipisha_customer_id');
                    
                    if (!empty($dbValue) && trim($dbValue) !== '' && trim($dbValue) !== '0' && $dbValue == $customerIdValue) {
                        break; // Success
                    }
                    
                    $verificationCount++;
                    if ($verificationCount < $verificationRetries) {
                        // Try one more direct update
                        \DB::table('students')
                            ->where('id', $student->id)
                            ->where(function($query) {
                                $query->whereNull('lipisha_customer_id')
                                      ->orWhere('lipisha_customer_id', '')
                                      ->orWhere('lipisha_customer_id', '0');
                            })
                            ->update(['lipisha_customer_id' => $customerIdValue]);
                        usleep(100000); // Wait 100ms
                    }
                }
                
                Log::info('💾 LIPISHA SAVE ATTEMPT RESULTS', [
                    'student_id' => $student->id,
                    'customer_id_attempted' => $customerIdValue,
                    'method1_db_update' => $updated1,
                    'method2_model_save' => $saved2,
                    'method3_raw_sql' => $updated3,
                    'final_db_value' => $dbValue,
                    'final_model_value' => $student->lipisha_customer_id,
                    'verification_retries' => $verificationCount,
                    'SUCCESS' => (!empty($dbValue) && $dbValue == $customerIdValue) ? '✅ YES' : '❌ NO'
                ]);
                
                // If still null after all attempts, log critical error
                if (empty($dbValue) || $dbValue != $customerIdValue) {
                    Log::error('❌❌❌ CRITICAL: customer_id STILL NULL AFTER ALL METHODS', [
                        'student_id' => $student->id,
                        'customer_id_attempted' => $customerIdValue,
                        'final_db_value' => $dbValue,
                        'all_methods_tried' => true,
                        'verification_retries' => $verificationCount
                    ]);
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                Log::error('❌ EXCEPTION saving customer_id', [
                    'student_id' => $student->id,
                    'customer_id' => $customerIdValue,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // Re-throw to trigger retry
            }
        } else {
            Log::warning('Failed to create LIPISHA customer for student - no customer_id in result', [
                'student_id' => $student->id,
                'error' => $result['message'] ?? 'Unknown error',
                'success' => $result['success'] ?? false,
                'has_customer_id' => isset($result['customer_id']),
                'customer_id_value' => $result['customer_id'] ?? 'not_set',
                'customer_id_empty' => empty($result['customer_id'] ?? null),
                'result_keys' => array_keys($result ?? []),
                'full_result' => $result
            ]);
        }

        return $result;
    }

    /**
     * Generate control number for invoice
     * Uses the student's customer_id to generate a unique control number per invoice
     * Control number format: {customer_id}-{invoice_id} or {customer_id}-{timestamp}
     */
    /**
     * Get control number for invoice from LIPISHA
     * Control number must be unique per quarter
     * Format: Use control_number from LIPISHA API response, or generate based on customer_id + quarter
     */
    /**
     * View/Retrieve customer from LIPISHA API
     * According to LIPISHA API documentation:
     * Endpoint: GET /v1/api/customers/view-customer
     * Query Parameters: customer_id (required)
     * Response: {"status": "success", "data": {"customer_id": <integer>, "name": "...", ...}}
     * 
     * @param int $customerId Customer ID to retrieve
     * @return array Result with 'success', 'customer_data', 'message', etc.
     */
    public static function viewCustomer($customerId)
    {
        // Check if LIPISHA is enabled
        if (!self::isEnabled()) {
            Log::info('LIPISHA integration is disabled - skipping customer view');
            return [
                'success' => false,
                'message' => 'LIPISHA integration is disabled'
            ];
        }

        try {
            $token = self::generateToken();
            
            if (!$token) {
                Log::error('Failed to generate LIPISHA token for customer view');
                return [
                    'success' => false,
                    'message' => 'Failed to authenticate with LIPISHA'
                ];
            }

            // According to documentation, endpoint is GET /v1/api/customers/view-customer
            // Query parameter: customer_id (required)
            // NOTE: If view-customer doesn't work, we can use list-customers and filter
            $url = "https://lipisha.co/v1/api/customers/view-customer";
            
            // Add customer_id as query parameter
            $url .= "?customer_id=" . urlencode((string)$customerId);
            
            // Alternative: Try using list-customers if view-customer fails
            // This will be handled in the error case below

            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer $token"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // GET is the default method, no need to set CURLOPT_GET
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('LIPISHA view customer curl error: ' . $curlError);
                return [
                    'success' => false,
                    'message' => 'Network error: ' . $curlError
                ];
            }

            // Log raw response for debugging
            Log::info('LIPISHA view customer response (RAW)', [
                'customer_id' => $customerId,
                'http_code' => $httpCode,
                'raw_response' => $response,
                'url' => $url
            ]);

            if ($httpCode !== 200) {
                $errorResult = json_decode($response, true);
                $errorMessage = $errorResult['message'] ?? ($errorResult['error'] ?? 'Unknown error');
                
                Log::error('LIPISHA view customer HTTP error', [
                    'http_code' => $httpCode,
                    'error_message' => $errorMessage,
                    'error_response' => $errorResult,
                    'raw_response' => $response,
                    'customer_id' => $customerId,
                    'url' => $url
                ]);
                
                return [
                    'success' => false,
                    'message' => 'API returned HTTP code: ' . $httpCode . ' - ' . $errorMessage,
                    'http_code' => $httpCode,
                    'error_response' => $errorResult
                ];
            }

            $result = json_decode($response, true);
            
            // According to documentation: {"status": "success", "data": {"customer_id": <integer>, ...}}
            if (isset($result['status']) && $result['status'] === 'success' && 
                isset($result['data']) && is_array($result['data'])) {
                
                Log::info('✅ LIPISHA customer retrieved successfully', [
                    'customer_id' => $customerId,
                    'customer_data' => $result['data']
                ]);
                
                return [
                    'success' => true,
                    'customer_data' => $result['data'],
                    'message' => $result['message'] ?? 'Customer retrieved successfully'
                ];
            } else {
                Log::warning('LIPISHA view customer - unexpected response format', [
                    'customer_id' => $customerId,
                    'response' => $result
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from LIPISHA API',
                    'response' => $result
                ];
            }
        } catch (\Exception $e) {
            Log::error('LIPISHA view customer exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get control number (bill_number) for invoice from LIPISHA
     * Control number must be unique per quarter and comes from billing/payment-reference API
     * According to LIPISHA API documentation, control_number (bill_number) is generated when creating payment reference
     */
    public static function getControlNumberForInvoice($student, $amount, $period = null, $academicYearId = null, $invoiceNumber = null, $description = null)
    {
        // School/College fee module removed - control number generation no longer supported
        return null;

        // Check if LIPISHA is enabled
        if (!self::isEnabled()) {
            Log::info('LIPISHA integration is disabled - skipping control number generation', [
                'student_id' => $student->id
            ]);
            return null;
        }

        // CRITICAL: Ensure student has LIPISHA customer_id before creating payment reference
        // If customer_id is missing, create it immediately (synchronous) - don't wait for job
        $customerId = null;
        
        // First, check if customer_id already exists in database
        $student->refresh(); // Refresh to get latest data
        $existingCustomerId = $student->lipisha_customer_id;
        
        if (!empty($existingCustomerId) && 
            trim($existingCustomerId) !== '' && 
            trim($existingCustomerId) !== '0' &&
            is_numeric($existingCustomerId)) {
            $customerId = (int) $existingCustomerId;
            
            Log::info('✅ LIPISHA: Using existing customer_id from database', [
                'student_id' => $student->id,
                'customer_id' => $customerId
            ]);
        } else {
            // Customer_id doesn't exist - create it immediately (synchronous)
            Log::warning('⚠️ LIPISHA: Student missing customer_id, creating immediately', [
                'student_id' => $student->id,
                'existing_customer_id' => $existingCustomerId
            ]);
            
            $result = self::getOrCreateCustomerForStudent($student);
            
            if ($result['success'] && isset($result['customer_id']) && 
                !empty($result['customer_id']) && 
                trim($result['customer_id']) !== '' && 
                trim($result['customer_id']) !== '0') {
                $customerId = (int) $result['customer_id'];
                
                // Verify it was saved to database
                $student->refresh();
                $savedCustomerId = $student->lipisha_customer_id;
                
                if (empty($savedCustomerId) || trim($savedCustomerId) !== (string)$customerId) {
                    Log::error('❌ CRITICAL: customer_id created but not saved to database', [
                        'student_id' => $student->id,
                        'created_customer_id' => $customerId,
                        'saved_customer_id' => $savedCustomerId
                    ]);
                    return null; // Can't proceed without customer_id
                }
                
                Log::info('✅ LIPISHA: customer_id created and saved successfully', [
                    'student_id' => $student->id,
                    'customer_id' => $customerId
                ]);
            } else {
                Log::error('❌ CRITICAL: Failed to create customer_id for student', [
                    'student_id' => $student->id,
                    'result' => $result
                ]);
                return null; // Can't proceed without customer_id
            }
        }
        
        // Now we have customer_id - proceed with payment reference creation
        if ($customerId && $customerId > 0) {
            
            // Create payment reference in LIPISHA to get bill_number (control number)
            // According to docs, this is where control_number comes from
            // Use FLEXIBLE amount type to allow partial payments
            $paymentRefResult = self::createPaymentReference(
                $customerId,
                $amount,
                $invoiceNumber,
                $description ?? "Fee invoice for {$student->first_name} {$student->last_name}",
                'FLEXIBLE', // Flexible amount - allows partial payments
                $period ? ['period' => $period, 'academic_year_id' => $academicYearId] : null
            );
            
            if ($paymentRefResult['success'] && isset($paymentRefResult['bill_number']) && !empty($paymentRefResult['bill_number'])) {
                $controlNumber = trim((string)$paymentRefResult['bill_number']);
                
                Log::info('✅✅✅ LIPISHA control number (bill_number) obtained from payment reference', [
                    'student_id' => $student->id,
                    'customer_id' => $customerId,
                    'period' => $period,
                    'academic_year_id' => $academicYearId,
                    'control_number' => $controlNumber,
                    'control_number_type' => gettype($controlNumber),
                    'control_number_length' => strlen($controlNumber),
                    'payment_ref_result' => $paymentRefResult
                ]);
                
                return $controlNumber;
            } else {
                // Log detailed error information
                Log::error('❌❌❌ Failed to get LIPISHA control number from payment reference', [
                    'student_id' => $student->id,
                    'customer_id' => $customerId,
                    'period' => $period,
                    'academic_year_id' => $academicYearId,
                    'amount' => $amount,
                    'payment_ref_success' => $paymentRefResult['success'] ?? false,
                    'payment_ref_has_bill_number' => isset($paymentRefResult['bill_number']),
                    'payment_ref_bill_number' => $paymentRefResult['bill_number'] ?? 'NOT_SET',
                    'payment_ref_bill_number_empty' => empty($paymentRefResult['bill_number'] ?? null),
                    'payment_ref_message' => $paymentRefResult['message'] ?? 'NOT_SET',
                    'payment_ref_data' => $paymentRefResult['data'] ?? 'NOT_SET',
                    'full_payment_ref_result' => $paymentRefResult
                ]);
                return null;
            }
        }
        
        Log::warning('Failed to get LIPISHA control number - no customer_id', [
            'student_id' => $student->id
        ]);
        
        return null;
    }

    /**
     * Create Payment Reference (Bill) in LIPISHA
     * According to LIPISHA API documentation:
     * Endpoint: POST /v1/api/billing/payment-reference
     * Response: {"status": "success", "data": {"bill_number": "<bill_number_string>", ...}}
     * 
     * @param int $customerId Customer ID (required if using customer management)
     * @param float $amount Payment amount (required)
     * @param string|null $invoice Invoice number (optional)
     * @param string|null $description Description (optional)
     * @param string $amountType Amount type: FIXED (exact amount), FLEXIBLE (any amount - allows partial payments), or LESS (below or exact) (default: FLEXIBLE for partial payments)
     * @param array|null $metadata Metadata as array (optional)
     * @return array Result with 'success', 'bill_number', 'message', etc.
     */
    public static function createPaymentReference($customerId, $amount, $invoice = null, $description = null, $amountType = 'FLEXIBLE', $metadata = null)
    {
        // Check if LIPISHA is enabled
        if (!self::isEnabled()) {
            Log::info('LIPISHA integration is disabled - skipping payment reference creation');
            return [
                'success' => false,
                'message' => 'LIPISHA integration is disabled'
            ];
        }

        // Increase PHP execution time limit to prevent timeout (2 minutes + buffer)
        @set_time_limit(150); // 150 seconds (2.5 minutes)

        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                $token = self::generateToken();
                
                if (!$token) {
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        Log::warning('Failed to generate LIPISHA token for payment reference, retrying...', [
                            'retry' => $retryCount,
                            'max_retries' => $maxRetries
                        ]);
                        usleep(500000); // Wait 500ms before retry
                        continue;
                    }
                    
                    Log::error('Failed to generate LIPISHA token for payment reference creation after retries');
                    return [
                        'success' => false,
                        'message' => 'Failed to authenticate with LIPISHA'
                    ];
                }

                $url = "https://lipisha.co/v1/api/billing/payment-reference";

                // Build request data according to documentation
                // According to docs: "Provide either customer or client, not both"
                // Since we're using customer management feature, we ONLY send "customer" parameter
                $data = [
                    "amount" => (float) $amount,
                    "amountType" => $amountType // FIXED, FLEXIBLE, or LESS
                ];

                // CRITICAL: Add customer ID to request (required if using customer management)
                // customer_id MUST come from LIPISHA API (from customer creation) - we use it here
                // IMPORTANT: Only send "customer" parameter, NOT "client" parameter
                // LIPISHA API requires either "customer" OR "client", not both
                if ($customerId && $customerId > 0 && is_numeric($customerId)) {
                    $data["customer"] = (int) $customerId; // Send customer_id from LIPISHA API
                    // DO NOT send "client" parameter - this causes "Provide either customer or client, not both" error
                    
                    Log::info('✅ LIPISHA payment reference: Using customer_id in request', [
                        'customer_id' => $customerId,
                        'customer_id_type' => gettype($customerId),
                        'customer_id_source' => 'LIPISHA_API',
                        'amount' => $amount,
                        'note' => 'This customer_id comes from LIPISHA customer creation API'
                    ]);
                } else {
                    Log::error('❌ LIPISHA payment reference: customer_id is missing or invalid', [
                        'customer_id' => $customerId,
                        'customer_id_type' => gettype($customerId),
                        'customer_id_is_numeric' => is_numeric($customerId),
                        'customer_id_positive' => ($customerId && $customerId > 0),
                        'amount' => $amount,
                        'note' => 'customer_id must come from LIPISHA API - cannot create payment reference without it'
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Customer ID is required for payment reference creation (must come from LIPISHA API)',
                        'bill_number' => null
                    ];
                }

                // Optional fields
                if ($invoice) {
                    $data["invoice"] = (string) $invoice;
                }
                if ($description) {
                    $data["description"] = (string) $description;
                }
                // Handle metadata - according to docs, metadata should be a JSON object/dict
                // CRITICAL: Only send metadata if it's a valid non-empty associative array (object-like)
                // If metadata is null, empty array, or invalid, DON'T send it at all (don't include in $data)
                // API will reject empty metadata or invalid JSON
                if ($metadata && is_array($metadata) && !empty($metadata)) {
                    // Check if it's an associative array (object-like) not indexed array
                    $isAssociative = array_keys($metadata) !== range(0, count($metadata) - 1);
                    
                    if ($isAssociative && !empty($metadata)) {
                        // According to LIPISHA API documentation, metadata is "Json/Dict"
                        // When we json_encode the entire request, metadata should be an array/object
                        // NOT a JSON string - let json_encode handle it
                        // Store metadata as array/object directly - json_encode will convert it correctly
                        $data["metadata"] = $metadata; // Store as array/object, not JSON string
                        
                        Log::info('✅ LIPISHA payment reference: Metadata included as array/object', [
                            'metadata_array' => $metadata,
                            'metadata_type' => 'ARRAY_OBJECT',
                            'note' => 'Will be JSON encoded with entire request'
                        ]);
                    } else {
                        Log::warning('LIPISHA payment reference: Metadata is not associative array or empty, skipping', [
                            'metadata' => $metadata,
                            'is_associative' => $isAssociative,
                            'is_empty' => empty($metadata)
                        ]);
                    }
                } else {
                    // Don't send metadata if it's null, empty, or not an array
                    // This is OK - metadata is optional according to documentation
                    Log::info('LIPISHA payment reference: Metadata not included (null/empty/invalid - this is OK)', [
                        'metadata' => $metadata,
                        'is_array' => is_array($metadata),
                        'is_empty' => empty($metadata)
                    ]);
                }
                
                // CRITICAL: Remove metadata from $data if it's empty or invalid to prevent API errors
                // API requires metadata to be a valid JSON object, not empty string or invalid JSON
                if (isset($data['metadata']) && (empty($data['metadata']) || $data['metadata'] === '[]' || $data['metadata'] === '{}')) {
                    unset($data['metadata']);
                    Log::warning('LIPISHA payment reference: Removed empty/invalid metadata from request', [
                        'metadata_value' => $data['metadata'] ?? 'NOT_SET'
                    ]);
                }
                
                // Log request data for debugging (without sensitive info)
                Log::info('🔍 LIPISHA payment reference request data', [
                    'amount' => $data['amount'],
                    'amountType' => $data['amountType'],
                    'has_customer' => isset($data['customer']),
                    'customer_id' => $data['customer'] ?? 'NOT_SET',
                    'has_client' => isset($data['client']), // Should be false
                    'has_invoice' => isset($data['invoice']),
                    'has_description' => isset($data['description']),
                    'has_metadata' => isset($data['metadata']),
                    'metadata_type' => isset($data['metadata']) ? gettype($data['metadata']) : 'NOT_SET',
                    'metadata_value' => isset($data['metadata']) ? (is_string($data['metadata']) ? substr($data['metadata'], 0, 100) : json_encode($data['metadata'])) : 'NOT_SET' // Log first 100 chars if string, or JSON if array
                ]);

                $headers = [
                    "Content-Type: application/json",
                    "Authorization: Bearer $token"
                ];

                // According to LIPISHA API documentation, metadata is "Json/Dict"
                // When we json_encode the entire request, metadata should be an array/object
                // json_encode will automatically convert it to JSON object in the request
                // Metadata is already stored as array in $data, so we can use it directly
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                
                // Log the actual request payload being sent
                Log::info('🔍 LIPISHA payment reference request payload (FINAL)', [
                    'request_data' => $data,
                    'json_encoded_payload' => json_encode($data),
                    'metadata_in_request' => isset($data['metadata']),
                    'metadata_type' => isset($data['metadata']) ? gettype($data['metadata']) : 'NOT_SET',
                    'metadata_value' => isset($data['metadata']) ? $data['metadata'] : 'NOT_SET'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Increased to 120 seconds (2 minutes) for slow networks
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // Increased connection timeout to 60 seconds
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // Use HTTP/1.1 for better compatibility

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    // Check if it's a timeout error
                    $isTimeout = (strpos(strtolower($curlError), 'timeout') !== false || 
                                 strpos(strtolower($curlError), 'timed out') !== false);
                    
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        Log::warning('LIPISHA payment reference creation curl error, retrying...', [
                            'error' => $curlError,
                            'is_timeout' => $isTimeout,
                            'retry' => $retryCount,
                            'max_retries' => $maxRetries
                        ]);
                        // Longer delay for timeout errors
                        $delay = $isTimeout ? (2000000 * $retryCount) : (1000000 * $retryCount);
                        usleep($delay);
                        continue;
                    }
                    
                    Log::error('LIPISHA payment reference creation curl error after retries: ' . $curlError);
                    return [
                        'success' => false,
                        'message' => $isTimeout ? 'Request timed out. Please check your internet connection and try again.' : 'Network error: ' . $curlError
                    ];
                }

                // According to LIPISHA API documentation:
                // Status Code 201 = Bill created successfully
                // Status Code 400 = Validation error
                // Status Code 500 = Internal server error
                // We accept both 200 and 201 as success codes (some APIs return 200 for creation)
                if ($httpCode !== 201 && $httpCode !== 200) {
                    // Parse error response for better error messages
                    $errorResult = json_decode($response, true);
                    $errorMessage = $errorResult['message'] ?? 'Unknown error';
                    
                    // Log detailed error information
                    Log::error('❌ LIPISHA payment reference creation HTTP error', [
                        'http_code' => $httpCode,
                        'error_message' => $errorMessage,
                        'error_response' => $errorResult,
                        'raw_response' => $response,
                        'request_data' => [
                            'amount' => $amount,
                            'amountType' => $amountType,
                            'customer_id' => $customerId,
                            'has_customer' => isset($data['customer']),
                            'has_client' => isset($data['client']), // Should be false
                            'invoice' => $invoice,
                            'description' => $description
                        ]
                    ]);
                    
                    // Don't retry on 400 (Bad Request) errors - these are validation errors
                    if ($httpCode === 400) {
                        return [
                            'success' => false,
                            'message' => 'API validation error: ' . $errorMessage,
                            'http_code' => $httpCode,
                            'error_response' => $errorResult,
                            'bill_number' => null
                        ];
                    }
                    
                    $retryCount++;
                    if ($retryCount < $maxRetries && ($httpCode >= 500 || $httpCode === 429)) {
                        // Retry on server errors (5xx) or rate limit (429)
                        Log::warning('LIPISHA payment reference creation HTTP error, retrying...', [
                            'http_code' => $httpCode,
                            'error_message' => $errorMessage,
                            'retry' => $retryCount,
                            'max_retries' => $maxRetries
                        ]);
                        usleep(1000000 * $retryCount); // Exponential backoff
                        continue;
                    }
                    
                    return [
                        'success' => false,
                        'message' => 'API returned HTTP code: ' . $httpCode . ' - ' . $errorMessage,
                        'http_code' => $httpCode,
                        'error_response' => $errorResult,
                        'bill_number' => null
                    ];
                }

                $result = json_decode($response, true);
                
                // Log full response for debugging
                Log::info('LIPISHA payment reference creation response (RAW)', [
                    'raw_response' => $response,
                    'decoded_response' => $result,
                    'response_type' => gettype($result),
                    'response_keys' => is_array($result) ? array_keys($result) : 'not_array',
                    'http_code' => $httpCode
                ]);

                // Extract bill_number from response following LIPISHA API documentation
                // According to docs: {"status": "success", "data": {"bill_number": "<bill_number_string>", ...}}
                $isSuccess = false;
                $billNumber = null;
                $message = '';

                // According to LIPISHA API documentation:
                // Response: {"status": "success", "message": "Bill created successfully", "data": {"bill_number": "<bill_number_string>", ...}}
                // HTTP Status Code: 201 (Created) = Bill created successfully
                if (is_array($result)) {
                    $status = $result['status'] ?? null;
                    $message = $result['message'] ?? '';
                    
                    // Check if status is "success" (as per documentation)
                    // Documentation says: "status": "success"
                    $isStatusSuccess = ($status === 'success' || $status === 'SUCCESS');
                    
                    // PRIORITY 1: Check data['bill_number'] (as per API documentation - PRIMARY METHOD)
                    // According to docs: data.bill_number is where bill_number is located
                    if (isset($result['data']) && is_array($result['data']) && 
                        isset($result['data']['bill_number']) && 
                        $result['data']['bill_number'] !== null && 
                        $result['data']['bill_number'] !== '' &&
                        strlen(trim((string)$result['data']['bill_number'])) > 0) {
                        $billNumber = trim((string)$result['data']['bill_number']);
                        $isSuccess = true;
                        
                        Log::info('✅✅✅ LIPISHA bill_number EXTRACTED from data.bill_number (PRIMARY - following documentation)', [
                            'bill_number' => $billNumber,
                            'bill_number_raw' => $result['data']['bill_number'],
                            'type' => gettype($result['data']['bill_number']),
                            'status' => $status,
                            'is_status_success' => $isStatusSuccess,
                            'http_code' => $httpCode,
                            'message' => $message
                        ]);
                    } else {
                        // Log why extraction failed - detailed debugging
                        Log::error('❌ LIPISHA bill_number extraction FAILED - checking response structure', [
                            'has_data_key' => isset($result['data']),
                            'data_is_array' => isset($result['data']) && is_array($result['data']),
                            'data_bill_number_exists' => isset($result['data']['bill_number']),
                            'data_bill_number_value' => $result['data']['bill_number'] ?? 'NOT_SET',
                            'data_bill_number_type' => isset($result['data']['bill_number']) ? gettype($result['data']['bill_number']) : 'NOT_SET',
                            'status' => $status,
                            'is_status_success' => $isStatusSuccess,
                            'message' => $message,
                            'http_code' => $httpCode,
                            'response_keys' => array_keys($result),
                            'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : 'NOT_SET',
                            'full_response' => $result
                        ]);
                    }
                } else {
                    Log::error('❌ LIPISHA payment reference response is not an array', [
                        'response_type' => gettype($result),
                        'response' => $result,
                        'http_code' => $httpCode
                    ]);
                }

                // Return result
                if ($isSuccess && $billNumber) {
                    Log::info('✅ LIPISHA payment reference created successfully', [
                        'bill_number' => $billNumber,
                        'amount' => $amount,
                        'customer_id' => $customerId,
                        'http_code' => $httpCode,
                        'response_status' => $status,
                        'response_message' => $message
                    ]);
                    
                    // Break out of retry loop on success
                    return [
                        'success' => true,
                        'bill_number' => $billNumber,
                        'message' => $message ?: 'Bill created successfully',
                        'data' => $result['data'] ?? $result
                    ];
                } else {
                    $errorMessage = $message ?: (isset($result['message']) ? $result['message'] : 'Unknown error');
                    
                    // Log the full response for debugging - CRITICAL for troubleshooting
                    Log::error('❌❌❌ LIPISHA payment reference creation FAILED - no bill_number extracted', [
                        'raw_response' => $response,
                        'decoded_response' => $result,
                        'response_type' => gettype($result),
                        'is_success' => $isSuccess,
                        'bill_number_found' => !empty($billNumber),
                        'bill_number_value' => $billNumber,
                        'error_message' => $errorMessage,
                        'http_code' => $httpCode,
                        'response_keys' => is_array($result) ? array_keys($result) : 'not_array',
                        'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : 'not_array',
                        'data_bill_number_exists' => isset($result['data']['bill_number']),
                        'data_bill_number_value' => $result['data']['bill_number'] ?? 'NOT_SET',
                        'data_bill_number_type' => isset($result['data']['bill_number']) ? gettype($result['data']['bill_number']) : 'NOT_SET',
                        'status' => $status ?? 'NOT_SET',
                        'customer_id' => $customerId,
                        'amount' => $amount
                    ]);
                    
                    // If bill_number not found, retry if we have retries left
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        Log::warning('LIPISHA payment reference creation - no bill_number, retrying...', [
                            'retry' => $retryCount,
                            'max_retries' => $maxRetries,
                            'response' => $result
                        ]);
                        usleep(1000000 * $retryCount); // Exponential backoff
                        continue;
                    }
                    
                    return [
                        'success' => false,
                        'message' => 'Failed to create payment reference: ' . $errorMessage,
                        'data' => $result,
                        'bill_number' => null
                    ];
                }
                
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    Log::warning('LIPISHA payment reference creation exception, retrying...', [
                        'error' => $e->getMessage(),
                        'retry' => $retryCount,
                        'max_retries' => $maxRetries
                    ]);
                    usleep(1000000 * $retryCount); // Exponential backoff
                    continue;
                }
                
                Log::error('LIPISHA payment reference creation exception after retries: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        // If we get here, all retries failed
        return [
            'success' => false,
            'message' => 'Failed to create payment reference after all retries'
        ];
    }

    /**
     * Recursively search for a key in an array
     */
    private static function recursiveSearch($array, $keys)
    {
        if (!is_array($array)) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($array[$key])) {
                return $array[$key];
            }
        }

        foreach ($array as $value) {
            if (is_array($value)) {
                $result = self::recursiveSearch($value, $keys);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }
}

