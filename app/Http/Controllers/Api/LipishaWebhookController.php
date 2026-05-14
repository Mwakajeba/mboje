<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LipishaWebhookController extends Controller
{
    /**
     * Handle LIPISHA webhook callbacks
     */
    public function handle(Request $request)
    {
        try {
            // Get the verify token from settings (same as provided code)
            $verifyToken = env('LIPISHA_VERIFY_TOKEN', SystemSetting::getValue('lipisha_verify_token', ''));
            
            if (empty($verifyToken)) {
                Log::error('LIPISHA verify token not configured');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Webhook not configured'
                ], 500);
            }

            // Get the raw POST body (same as file_get_contents('php://input') in provided code)
            $rawPayload = $request->getContent();
            
            if (empty($rawPayload)) {
                Log::warning('LIPISHA webhook received empty payload');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Empty payload'
                ], 400);
            }

            // Get the signature from the headers (same as getallheaders()['X-Webhook-Signature'] in provided code)
            $receivedSignature = $request->header('X-Webhook-Signature');

            if (empty($receivedSignature)) {
                Log::warning('LIPISHA webhook received without signature');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing signature'
                ], 401);
            }

            // Compute the HMAC hash (same as hash_hmac('sha256', $rawPayload, $verify_token) in provided code)
            $calculatedSignature = hash_hmac('sha256', $rawPayload, $verifyToken);

            // Verify signature using hash_equals for timing attack prevention (same as provided code)
            if (!hash_equals($calculatedSignature, $receivedSignature)) {
                Log::warning('LIPISHA webhook signature verification failed', [
                    'received_length' => strlen($receivedSignature),
                    'calculated_length' => strlen($calculatedSignature),
                    'payload_length' => strlen($rawPayload)
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid signature'
                ], 401);
            }
            
            Log::info('LIPISHA webhook signature verified successfully');

            // Parse the payload (same as json_decode($rawPayload, true) in provided code)
            $data = json_decode($rawPayload, true);

            if (!$data) {
                Log::warning('LIPISHA webhook received invalid JSON', [
                    'payload_preview' => substr($rawPayload, 0, 200)
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid data'
                ], 400);
            }

            Log::info('LIPISHA webhook received and verified', [
                'status' => $data['status'] ?? 'unknown',
                'has_transaction_id' => isset($data['transaction_id']),
                'has_customer' => isset($data['customer']) || isset($data['customer_id'])
            ]);

            // Process the webhook data according to LIPISHA API documentation
            // Webhook payload contains: bill_number, amount, receipt, transactionRef, transactionDate, bill_id, paymentID, metadata
            // Note: Webhook doesn't have 'status' field - it's sent when payment is received
            if ($data) {
                // Extract data according to LIPISHA webhook documentation
                $billNumber = $data['bill_number'] ?? null; // This is the control number
                $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
                $receipt = $data['receipt'] ?? null;
                $transactionRef = $data['transactionRef'] ?? $data['transaction_ref'] ?? null;
                $transactionDate = $data['transactionDate'] ?? $data['transaction_date'] ?? null;
                $billId = $data['bill_id'] ?? null;
                $paymentId = $data['paymentID'] ?? $data['payment_id'] ?? null;
                $metadata = $data['metadata'] ?? null;

                Log::info('LIPISHA webhook payment received', [
                    'bill_number' => $billNumber,
                    'amount' => $amount,
                    'receipt' => $receipt,
                    'transaction_ref' => $transactionRef
                ]);

                // School/College fee invoice module removed - no invoice lookup
                $invoice = null;

                if ($invoice) {
                    DB::beginTransaction();
                    try {
                        // Update invoice payment status
                        $newPaidAmount = $invoice->paid_amount + $amount;
                        $invoice->paid_amount = $newPaidAmount;
                        
                        // Update status if fully paid
                        if ($newPaidAmount >= $invoice->total_amount) {
                            $invoice->status = 'paid';
                        }
                        
                        $invoice->save();

                        // Log the payment transaction
                        Log::info('LIPISHA payment processed', [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'bill_number' => $billNumber,
                            'receipt' => $receipt,
                            'transaction_ref' => $transactionRef,
                            'amount' => $amount,
                            'total_paid' => $newPaidAmount
                        ]);

                        DB::commit();

                        return response()->json([
                            'status' => 'received',
                            'bill_number' => $billNumber,
                            'receipt' => $receipt,
                            'transaction_ref' => $transactionRef,
                            'invoice_id' => $invoice->id
                        ], 200);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error processing LIPISHA payment', [
                            'error' => $e->getMessage(),
                            'invoice_id' => $invoice->id ?? null
                        ]);
                        
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Failed to process payment'
                        ], 500);
                    }
                } else {
                    Log::warning('LIPISHA payment received for unknown invoice', [
                        'bill_number' => $billNumber,
                        'invoice' => $data['invoice'] ?? null,
                        'transaction_ref' => $transactionRef
                    ]);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invoice not found'
                    ], 404);
                }
            } else {
                Log::warning('LIPISHA webhook received empty or invalid data', ['data' => $data]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid webhook data'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('LIPISHA webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
