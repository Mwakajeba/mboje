<?php

namespace App\Services;

use App\Models\GLTransaction;
use App\Models\SystemSetting;
use App\Models\Hotel\Booking;
use App\Models\Property\Lease;
use App\Models\Hotel\Property;
use Illuminate\Support\Facades\DB;

class HotelPropertyGLService
{
    /**
     * Create GL transactions for hotel booking
     */
    public function createBookingGLTransactions(Booking $booking)
    {
        DB::beginTransaction();
        
        try {
            // 1. Credit: Hotel Room Revenue (always create revenue)
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('hotel_room_revenue_account_id'),
                'amount' => $booking->total_amount,
                'nature' => 'credit',
                'transaction_type' => 'hotel_booking',
                'reference_id' => $booking->id,
                'description' => "Room revenue for booking #{$booking->booking_number}",
                'date' => $booking->check_in,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 2. Debit: Accounts Receivable (for unpaid amount)
            if ($booking->balance_due > 0) {
                GLTransaction::create([
                    'chart_account_id' => SystemSetting::getValue('accounts_receivable_account_id'),
                    'amount' => $booking->balance_due,
                    'nature' => 'debit',
                    'transaction_type' => 'hotel_booking',
                    'reference_id' => $booking->id,
                    'description' => "Receivable for booking #{$booking->booking_number}",
                    'date' => $booking->check_in,
                    'branch_id' => current_branch_id(),
                    'user_id' => auth()->id()
                ]);
            }
            
            // 3. Debit: Cash/Bank (for paid amount)
            if ($booking->paid_amount > 0) {
                // Get the appropriate account based on payment method
                $cashAccountId = $this->getCashAccountId($booking);
                
                GLTransaction::create([
                    'chart_account_id' => $cashAccountId,
                    'amount' => $booking->paid_amount,
                    'nature' => 'debit',
                    'transaction_type' => 'hotel_payment',
                    'reference_id' => $booking->id,
                    'description' => "Payment received for booking #{$booking->booking_number}",
                    'date' => now(),
                    'branch_id' => current_branch_id(),
                    'user_id' => auth()->id()
                ]);
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the appropriate cash account ID based on payment method
     */
    private function getCashAccountId(Booking $booking)
    {
        // Check if there's a receipt with bank account info
        $receipt = \App\Models\Receipt::where('reference_type', 'hotel_booking')
            ->where('reference_id', $booking->id)
            ->first();
            
        if ($receipt && $receipt->bank_account_id) {
            // Use the bank account's GL account
            $bankAccount = \App\Models\BankAccount::find($receipt->bank_account_id);
            return $bankAccount->chart_account_id ?? SystemSetting::getValue('cash_account_id');
        }
        
        // Default to cash account
        return SystemSetting::getValue('cash_account_id');
    }

    /**
     * Create GL transactions for lease agreement
     */
    public function createLeaseGLTransactions(Lease $lease)
    {
        DB::beginTransaction();
        
        try {
            // 1. Debit: Security Deposit Receivable
            if ($lease->deposit_balance > 0) {
                GLTransaction::create([
                    'chart_account_id' => SystemSetting::getValue('accounts_receivable_account_id'),
                    'amount' => $lease->deposit_balance,
                    'nature' => 'debit',
                    'transaction_type' => 'lease_deposit',
                    'reference_id' => $lease->id,
                    'description' => "Security deposit receivable for lease #{$lease->lease_number}",
                    'date' => $lease->start_date,
                    'branch_id' => current_branch_id(),
                    'user_id' => auth()->id()
                ]);
            }
            
            // 2. Credit: Security Deposit Liability
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('security_deposit_liability_account_id'),
                'amount' => $lease->security_deposit,
                'nature' => 'credit',
                'transaction_type' => 'lease_deposit',
                'reference_id' => $lease->id,
                'description' => "Security deposit liability for lease #{$lease->lease_number}",
                'date' => $lease->start_date,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 3. Debit: Cash/Bank (if deposit paid)
            if ($lease->paid_deposit > 0) {
                GLTransaction::create([
                    'chart_account_id' => SystemSetting::getValue('cash_account_id'),
                    'amount' => $lease->paid_deposit,
                    'nature' => 'debit',
                    'transaction_type' => 'lease_deposit_payment',
                    'reference_id' => $lease->id,
                    'description' => "Security deposit payment for lease #{$lease->lease_number}",
                    'date' => now(),
                    'branch_id' => current_branch_id(),
                    'user_id' => auth()->id()
                ]);
                
                // 4. Credit: Security Deposit Receivable (reducing the receivable)
                GLTransaction::create([
                    'chart_account_id' => SystemSetting::getValue('accounts_receivable_account_id'),
                    'amount' => $lease->paid_deposit,
                    'nature' => 'credit',
                    'transaction_type' => 'lease_deposit_payment',
                    'reference_id' => $lease->id,
                    'description' => "Security deposit payment for lease #{$lease->lease_number}",
                    'date' => now(),
                    'branch_id' => current_branch_id(),
                    'user_id' => auth()->id()
                ]);
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create GL transactions for monthly rent collection
     */
    public function createRentCollectionGLTransactions(Lease $lease, $amount, $date = null)
    {
        $date = $date ?: now();
        
        DB::beginTransaction();
        
        try {
            // 1. Debit: Cash/Bank
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('cash_account_id'),
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_type' => 'rent_collection',
                'reference_id' => $lease->id,
                'description' => "Rent collection for lease #{$lease->lease_number}",
                'date' => $date,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 2. Credit: Property Rental Income
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('property_rental_income_account_id'),
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_type' => 'rent_collection',
                'reference_id' => $lease->id,
                'description' => "Rent income for lease #{$lease->lease_number}",
                'date' => $date,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create GL transactions for property purchase
     */
    public function createPropertyPurchaseGLTransactions(Property $property)
    {
        DB::beginTransaction();
        
        try {
            // 1. Debit: Property Asset
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('property_asset_account_id'),
                'amount' => $property->purchase_price,
                'nature' => 'debit',
                'transaction_type' => 'property_purchase',
                'reference_id' => $property->id,
                'description' => "Property purchase: {$property->name}",
                'date' => $property->purchase_date,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 2. Credit: Cash/Bank (assuming cash purchase)
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('cash_account_id'),
                'amount' => $property->purchase_price,
                'nature' => 'credit',
                'transaction_type' => 'property_purchase',
                'reference_id' => $property->id,
                'description' => "Property purchase payment: {$property->name}",
                'date' => $property->purchase_date,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create GL transactions for hotel service revenue
     */
    public function createServiceRevenueGLTransactions($amount, $description, $referenceId = null, $referenceType = 'hotel_service')
    {
        DB::beginTransaction();
        
        try {
            // 1. Debit: Cash/Bank
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('cash_account_id'),
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => now(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 2. Credit: Hotel Service Revenue
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('hotel_service_revenue_account_id'),
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => now(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create GL transactions for property maintenance expense
     */
    public function createMaintenanceExpenseGLTransactions($amount, $description, $referenceId = null, $referenceType = 'property_maintenance')
    {
        DB::beginTransaction();
        
        try {
            // 1. Debit: Property Maintenance Expense
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('property_maintenance_expense_account_id'),
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => now(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 2. Credit: Cash/Bank
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('cash_account_id'),
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => now(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create GL transactions for late fee collection
     */
    public function createLateFeeGLTransactions(Lease $lease, $amount)
    {
        DB::beginTransaction();
        
        try {
            // 1. Debit: Cash/Bank
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('cash_account_id'),
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_type' => 'late_fee_collection',
                'reference_id' => $lease->id,
                'description' => "Late fee collection for lease #{$lease->lease_number}",
                'date' => now(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            // 2. Credit: Late Fee Income
            GLTransaction::create([
                'chart_account_id' => SystemSetting::getValue('property_late_fee_account_id'),
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_type' => 'late_fee_collection',
                'reference_id' => $lease->id,
                'description' => "Late fee income for lease #{$lease->lease_number}",
                'date' => now(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
