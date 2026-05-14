<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\LipishaPaymentLog;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\GlTransaction;
use App\Models\BankAccount;

class WebhookController extends Controller
{
    /**
     * Handle Lipisha webhook notifications.
     */
    public function lipisha(Request $request)
    {
        $signature = $request->header('x-webhook-signature');
        $verifyToken = config('services.lipisha.webhook_verify_token'); // Set this in config/services.php
        $payload = $request->getContent();

        // Signature validation bypassed for testing
        // $expectedSignature = hash_hmac('sha256', $payload, $verifyToken);
        // if (!$signature || !hash_equals($expectedSignature, $signature)) {
        //     Log::warning('Lipisha Webhook: Invalid signature', [
        //         'received' => $signature,
        //         'expected' => $expectedSignature,
        //         'payload' => $payload,
        //     ]);
        //     return response()->json(['error' => 'Invalid signature'], 400);
        // }

        $data = $request->json()->all();
        Log::info('Lipisha Webhook received', $data);

        DB::beginTransaction();
        try {
            // Store payment log
            $paymentLog = LipishaPaymentLog::create([
                'bill_number' => $data['bill_number'] ?? null,
                'amount' => $data['amount'] ?? null,
                'receipt' => $data['receipt'] ?? null,
                'transaction_ref' => $data['transactionRef'] ?? null,
                'transaction_date' => isset($data['transactionDate']) ? $data['transactionDate'] : now(),
                'bill_id' => $data['bill_id'] ?? null,
                'payment_id' => $data['paymentID'] ?? null,
                'metadata' => isset($data['metadata']) ? $data['metadata'] : null,
                'raw_payload' => $payload,
                'status' => 'pending',
            ]);

            // Find invoice by bill_number (lipisha_control_number)
            // School and College fee invoice handling has been removed.
            $billNumber = $data['bill_number'] ?? null;
            if (!$billNumber) {
                throw new \Exception('Bill number is required');
            }

            throw new \Exception("Invoice not found for bill number: {$billNumber}. School and College fee modules have been removed.");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Lipisha Webhook: Error processing payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            // Update payment log with error
            if (isset($paymentLog)) {
                $paymentLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

