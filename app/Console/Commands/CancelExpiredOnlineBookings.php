<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hotel\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelExpiredOnlineBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:cancel-expired-online';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete online bookings that are older than 2 hours and not confirmed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired online bookings...');
        
        // Get expiry hours from settings for each company
        // Process bookings for all companies
        $companies = \App\Models\Company::all();
        
        $totalDeleted = 0;
        
        foreach ($companies as $company) {
            $expiryHours = (int) \App\Models\Hotel\HotelPortalSetting::getSetting(
                $company->id,
                'online_booking_expiry_hours',
                2
            );
            
            // Get online bookings created more than expiry hours ago
            // Only delete online_booking status, not confirmed ones
            $expiredBookings = Booking::where('status', 'online_booking')
                ->where('company_id', $company->id)
                ->where('created_at', '<=', Carbon::now()->subHours($expiryHours))
                ->get();
            
            if ($expiredBookings->isEmpty()) {
                continue;
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($expiredBookings as $booking) {
                    $bookingNumber = $booking->booking_number;
                    $bookingId = $booking->id;
                    
                    // Delete the booking (this will also delete related records if cascade is set up)
                    $booking->delete();
                    
                    $totalDeleted++;
                    $this->line("Deleted booking: {$bookingNumber} (Company: {$company->name})");
                    
                    Log::info('Online booking auto-deleted', [
                        'booking_id' => $bookingId,
                        'booking_number' => $bookingNumber,
                        'company_id' => $company->id,
                        'expiry_hours' => $expiryHours,
                        'created_at' => $booking->created_at,
                    ]);
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error deleting expired bookings for company {$company->name}: " . $e->getMessage());
                Log::error('Error deleting expired online bookings', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        if ($totalDeleted > 0) {
            $this->info("Successfully deleted {$totalDeleted} expired online booking(s).");
        } else {
            $this->info('No expired online bookings found.');
        }
        
        return 0;
    }
}
