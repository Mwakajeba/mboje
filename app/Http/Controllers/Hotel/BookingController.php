<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Booking;
use App\Models\Hotel\Room;
use App\Models\Hotel\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view bookings');
        $branch_id = session('branch_id') ?? user()->branch_id ?? 1;
        
        // Clean up expired online bookings when viewing online bookings page
        if ($request->has('status') && $request->status === 'online_booking') {
            $this->cleanupExpiredOnlineBookings($branch_id);
        }
        
        if ($request->ajax()) {
            // Calendar view request
            if ($request->has('calendar') && $request->calendar == 'true') {
                $bookings = Booking::with(['room', 'guest'])
                    ->where('branch_id', $branch_id)
                    ->where(function($q) use ($request) {
                        if ($request->has('start') && $request->has('end')) {
                            $q->where(function($query) use ($request) {
                                $query->whereBetween('check_in', [$request->start, $request->end])
                                      ->orWhereBetween('check_out', [$request->start, $request->end])
                                      ->orWhere(function($subQ) use ($request) {
                                          $subQ->where('check_in', '<=', $request->start)
                                               ->where('check_out', '>=', $request->end);
                                      });
                            });
                        }
                    })
                    ->get();

                return response()->json([
                    'data' => $bookings->map(function($booking) {
                        return [
                            'booking_number' => $booking->booking_number,
                            'room_number' => $booking->room->room_number ?? 'N/A',
                            'guest_name' => ($booking->guest->first_name ?? '') . ' ' . ($booking->guest->last_name ?? ''),
                            'check_in' => $booking->check_in->format('Y-m-d'),
                            'check_out' => $booking->check_out->format('Y-m-d'),
                            'status' => $booking->status,
                        ];
                    })
                ]);
            }

            // DataTable request
            $bookings = Booking::with(['room', 'guest', 'createdBy'])
                ->where('branch_id', $branch_id)
                ->select('bookings.*');

            // Filter by status if provided
            if ($request->has('status') && $request->status) {
                $bookings->where('status', $request->status);
            } else {
                // By default, exclude online_booking status from main bookings page
                // Online bookings should only be shown when explicitly filtered
                $bookings->where('status', '!=', 'online_booking');
            }

            return DataTables::of($bookings)
                ->filter(function ($query) use ($request) {
                    $searchValue = $request->input('search.value');
                    if (!empty($searchValue)) {
                        $query->where(function ($q) use ($searchValue) {
                            $q->where('bookings.booking_number', 'like', "%{$searchValue}%")
                              ->orWhere('bookings.id', 'like', "%{$searchValue}%")
                              ->orWhereHas('guest', function ($guestQuery) use ($searchValue) {
                                  $guestQuery->where('first_name', 'like', "%{$searchValue}%")
                                      ->orWhere('last_name', 'like', "%{$searchValue}%")
                                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchValue}%"]);
                              })
                              ->orWhereHas('room', function ($roomQuery) use ($searchValue) {
                                  $roomQuery->where('room_number', 'like', "%{$searchValue}%")
                                      ->orWhere('room_type', 'like', "%{$searchValue}%");
                              });
                        });
                    }
                }, false)
                ->addColumn('guest_name', function ($booking) {
                    return $booking->guest_name;
                })
                ->addColumn('room_info', function ($booking) {
                    return $booking->room_info;
                })
                ->addColumn('check_in_formatted', function ($booking) {
                    return $booking->check_in ? $booking->check_in->format('M d, Y') : 'N/A';
                })
                ->addColumn('check_out_formatted', function ($booking) {
                    return $booking->check_out ? $booking->check_out->format('M d, Y') : 'N/A';
                })
                ->addColumn('status_badge', function ($booking) {
                    return $booking->status_badge;
                })
                ->addColumn('payment_status_badge', function ($booking) {
                    return $booking->payment_status_badge;
                })
                ->addColumn('total_amount_formatted', function ($booking) {
                    return 'TSh ' . number_format($booking->total_amount ?? 0, 0);
                })
                ->addColumn('actions', function ($booking) {
                    return $booking->actions;
                })
                ->rawColumns(['guest_name', 'room_info', 'status_badge', 'payment_status_badge', 'actions'])
                ->make(true);
        }

        $totalBookings = Booking::where('branch_id', $branch_id)->count();
        $confirmedBookings = Booking::where('branch_id', $branch_id)->where('status', 'confirmed')->count();
        $checkedInBookings = Booking::where('branch_id', $branch_id)->where('status', 'checked_in')->count();
        $pendingBookings = Booking::where('branch_id', $branch_id)->where('status', 'pending')->count();
        $onlineBookings = Booking::where('branch_id', $branch_id)->where('status', 'online_booking')->count();
        $cancelledBookings = Booking::where('branch_id', $branch_id)->where('status', 'cancelled')->count();

        // Get web portal settings
        $user = Auth::user();
        $companyId = $user->company_id;
        $settings = \App\Models\Hotel\HotelPortalSetting::where('company_id', $companyId)
            ->whereIn('setting_key', [
                'enable_booking_portal',
                'require_admin_approval',
                'online_booking_expiry_hours',
                'booking_availability_hours',
                'enable_dynamic_pricing',
                'weekend_rate_multiplier',
                'enable_promo_codes',
                'enable_captcha',
                'portal_notification_email',
                'portal_notification_sms',
                'enable_email_verification',
                'portal_tax_rate',
                'portal_terms_conditions'
            ])
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        // Set defaults
        $defaultSettings = [
            'enable_booking_portal' => '1',
            'require_admin_approval' => '0',
            'online_booking_expiry_hours' => '2',
            'booking_availability_hours' => '24',
            'enable_dynamic_pricing' => '0',
            'weekend_rate_multiplier' => '1.2',
            'enable_promo_codes' => '1',
            'enable_captcha' => '1',
            'portal_notification_email' => '',
            'portal_notification_sms' => '',
            'enable_email_verification' => '0',
            'portal_tax_rate' => '18',
            'portal_terms_conditions' => ''
        ];

        foreach ($defaultSettings as $key => $defaultValue) {
            if (!isset($settings[$key])) {
                $settings[$key] = $defaultValue;
            }
        }

        return view('hotel.bookings.index', compact(
            'totalBookings',
            'confirmedBookings',
            'checkedInBookings',
            'pendingBookings',
            'onlineBookings',
            'cancelledBookings',
            'settings'
        ));
    }

    public function create()
    {
        $this->authorize('create booking');
        
        $branch_id = session('branch_id') ?? Auth::user()->branch_id ?? 1;
        
        // List rooms for the current branch; availability will be validated against selected dates
        $rooms = Room::with('property')
            ->where('branch_id', $branch_id)
            ->orderBy('room_number')
            ->get();
            
        $guests = Guest::active()->orderBy('first_name')->get();
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get();
        
        return view('hotel.bookings.create', compact('rooms', 'guests', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $this->authorize('create booking');
        
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
            'room_rate' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:pending,confirmed,checked_in,checked_out,cancelled',
            'booking_source' => 'nullable|string|in:walk_in,phone,online,agent,other',
            'special_requests' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'paid_amount' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id'
        ]);

        DB::beginTransaction();
        
        try {
            Log::info('Booking: store request received', [
                'user_id' => Auth::id(),
                'guest_id' => $request->guest_id,
                'room_id' => $request->room_id,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
            ]);
            // Check room availability
            $room = Room::findOrFail($request->room_id);
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            
            if (!$room->isAvailableForDates($checkIn, $checkOut)) {
                // Find overlapping booking to show precise range
                $conflict = $room->bookings()
                    ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                    ->where(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<', $checkOut)
                          ->where('check_out', '>', $checkIn);
                    })
                    ->orderBy('check_in')
                    ->first();

                $conflictMsg = 'Room is not available for the selected dates.';
                if ($conflict) {
                    $conflictMsg = sprintf(
                        'Your new range %s 																				→ %s overlaps an existing booking %s → %s. Please choose dates outside this range.',
                        $checkIn->format('Y-m-d'),
                        $checkOut->format('Y-m-d'),
                        $conflict->check_in->format('Y-m-d'),
                        $conflict->check_out->format('Y-m-d')
                    );
                }

                Log::warning('Booking: room unavailable for selected dates', [
                    'room_id' => $room->id,
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'conflict_booking_id' => $conflict->id ?? null
                ]);
                return redirect()->back()
                    ->withInput()
                    ->with('error', $conflictMsg)
                    ->with('error_overlap', true);
            }

            // Calculate nights and total amount
            $nights = $checkIn->diffInDays($checkOut);
            $grossAmount = $request->room_rate * $nights;
            $discountAmount = $request->discount_amount ?? 0;
            $totalAmount = max(0, $grossAmount - $discountAmount);
            $paidAmount = $request->paid_amount ?? 0;
            $balanceDue = $totalAmount - $paidAmount;
            
            // Calculate payment status automatically
            $paymentStatus = 'pending';
            if ($paidAmount > 0 && $paidAmount < $totalAmount) {
                $paymentStatus = 'partial';
            } elseif ($paidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            }

            // Generate unique booking number
            $bookingModel = new Booking();
            $bookingNumber = $bookingModel->generateBookingNumber();
            $branch_id = session('branch_id') ?? user()->branch_id ?? 1;

            $booking = Booking::create([
                'booking_number' => $bookingNumber,
                'room_id' => $request->room_id,
                'guest_id' => $request->guest_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $request->adults,
                'children' => $request->children ?? 0,
                'nights' => $nights,
                'room_rate' => $request->room_rate,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'paid_amount' => $paidAmount,
                'balance_due' => $balanceDue,
                'status' => $request->status,
                'payment_status' => $paymentStatus,
                'booking_source' => $request->booking_source ?? 'walk_in',
                'special_requests' => $request->special_requests,
                'notes' => $request->notes,
                'branch_id' => $branch_id,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id()
            ]);

            // Update room status if booking is confirmed or checked in AND is for current dates
            if (in_array($request->status, ['confirmed', 'checked_in']) && $checkIn <= now()) {
                $room->update(['status' => 'occupied']);
            }

            // Create receipt for any paid amount
            if ($booking->paid_amount > 0) {
                $this->createBookingReceipt($booking, $request);
            }
            
            // Create GL transactions for the booking
            $this->createBookingGLTransactions($booking);

            DB::commit();
            
            Log::info('Booking: created successfully', ['booking_id' => $booking->id]);
            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Booking created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking: failed to create', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create booking: ' . $e->getMessage());
        }
    }

    public function show(Booking $booking)
    {
        $this->authorize('view booking details');
        
        $booking->load([
            'room.property', 
            'guest', 
            'createdBy',
            'glTransactions.chartAccount',
            'paymentGlTransactions.chartAccount',
            'receipts.receiptItems.chartAccount',
            'receipts.bankAccount'
        ]);
        
        return view('hotel.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking)
    {
        $this->authorize('edit booking');
        
        // Prevent editing checked-out bookings
        if ($booking->status === 'checked_out') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Cannot edit checked-out bookings. This booking has been completed.');
        }
        
        // Prevent editing bookings that have receipts
        if ($booking->receipts()->count() > 0) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Cannot edit booking with receipts. Please delete all receipts before editing this booking.');
        }
        
        $branch_id = session('branch_id') ?? Auth::user()->branch_id ?? 1;
        
        $rooms = Room::with('property')
            ->where('branch_id', $branch_id)
            ->orderBy('room_number')
            ->get();
        $guests = Guest::active()->orderBy('first_name')->get();
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get();
        
        return view('hotel.bookings.edit', compact('booking', 'rooms', 'guests', 'bankAccounts'));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->authorize('edit booking');
        
        // Prevent updating checked-out bookings
        if ($booking->status === 'checked_out') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Cannot update checked-out bookings. This booking has been completed.');
        }
        
        // Prevent updating bookings that have receipts
        if ($booking->receipts()->count() > 0) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Cannot update booking with receipts. Please delete all receipts before editing this booking.');
        }
        
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
            'room_rate' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:pending,confirmed,checked_in,checked_out,cancelled',
            'booking_source' => 'nullable|string|in:walk_in,phone,online,agent,other',
            'special_requests' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'paid_amount' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id'
        ]);

        DB::beginTransaction();
        
        try {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $nights = $checkIn->diffInDays($checkOut);
            $grossAmount = $request->room_rate * $nights;
            $discountAmount = $request->discount_amount ?? ($booking->discount_amount ?? 0);
            $totalAmount = max(0, $grossAmount - $discountAmount);
            
            // Handle payment changes
            $oldPaidAmount = $booking->paid_amount;
            $newPaidAmount = $request->paid_amount ?? 0;
            $paymentDifference = $newPaidAmount - $oldPaidAmount;
            
            $balanceDue = $totalAmount - $newPaidAmount;
            
            // Calculate payment status automatically
            $paymentStatus = 'pending';
            if ($newPaidAmount > 0 && $newPaidAmount < $totalAmount) {
                $paymentStatus = 'partial';
            } elseif ($newPaidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            }

            $booking->update([
                'room_id' => $request->room_id,
                'guest_id' => $request->guest_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $request->adults,
                'children' => $request->children ?? 0,
                'nights' => $nights,
                'room_rate' => $request->room_rate,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $newPaidAmount,
                'balance_due' => $balanceDue,
                'status' => $request->status,
                'payment_status' => $paymentStatus,
                'booking_source' => $request->booking_source ?? $booking->booking_source ?? 'walk_in',
                'special_requests' => $request->special_requests,
                'notes' => $request->notes
            ]);

            // Recreate booking GL transactions to reflect changes (revenue/discount/AR/Cash)
            $booking->glTransactions()->delete();
            $this->createBookingGLTransactions($booking);

            // Create receipt for additional payment
            if ($paymentDifference > 0) {
                $this->createBookingReceipt($booking, $request, $paymentDifference);
                
                // Create GL transactions for the additional payment
                $this->createPaymentGLTransactions($booking, $paymentDifference, $request);
            }

            DB::commit();
            
            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Booking updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update booking: ' . $e->getMessage());
        }
    }

    public function destroy(Booking $booking)
    {
        $this->authorize('delete booking');
        
        try {
            if (($booking->paid_amount ?? 0) > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete booking with received amount. Only bookings with 0 received amount can be deleted.');
            }

            DB::transaction(function () use ($booking) {
                // Delete receipts and linked records for this booking number
                $receipts = $booking->receipts()->get();
                foreach ($receipts as $receipt) {
                    $receipt->glTransactions()->delete();
                    $receipt->receiptItems()->delete();
                    $receipt->delete();
                }

                // Delete booking GL transactions
                $booking->glTransactions()->delete();
                $booking->paymentGlTransactions()->delete();

                // Permanently delete booking
                $booking->forceDelete();
            });
            
            return redirect()->route('bookings.index')
                ->with('success', 'Booking deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete booking: ' . $e->getMessage());
        }
    }

    public function checkIn(Booking $booking)
    {
        $this->authorize('edit booking');
        
        if ($booking->checkIn()) {
            return redirect()->back()
                ->with('success', 'Guest checked in successfully!');
        }
        
        return redirect()->back()
            ->with('error', 'Cannot check in guest at this time.');
    }

    public function checkOut(Booking $booking)
    {
        $this->authorize('edit booking');
        
        if ($booking->checkOut()) {
            return redirect()->back()
                ->with('success', 'Guest checked out successfully!');
        }
        
        return redirect()->back()
            ->with('error', 'Cannot check out guest at this time.');
    }

    public function confirm(Booking $booking)
    {
        $this->authorize('edit booking');

        // Allow confirming from pending or online_booking status
        if (!in_array($booking->status, ['pending', 'online_booking'])) {
            return redirect()->back()->with('error', 'Only pending or online bookings can be confirmed.');
        }

        // Basic room availability guard (optional): ensure check-in date not in the past for unavailable rooms
        if ($booking->check_in && $booking->check_in < now() && optional($booking->room)->status === 'occupied') {
            return redirect()->back()->with('error', 'Room is currently occupied. Cannot confirm.');
        }

        $booking->update(['status' => 'confirmed']);

        return redirect()->back()->with('success', 'Booking confirmed successfully!');
    }

    /**
     * Accept an online booking (changes status from online_booking to confirmed)
     * This prevents auto-cancellation and moves it to regular bookings
     */
    public function accept(Booking $booking)
    {
        $this->authorize('edit booking');

        // Only allow accepting online_booking status
        if ($booking->status !== 'online_booking') {
            return redirect()->back()->with('error', 'Only online bookings can be accepted.');
        }

        // Check room availability
        $checkIn = $booking->check_in;
        $checkOut = $booking->check_out;
        
        // Check if room is available (excluding online_booking status as they can be cancelled)
        $hasOverlappingBooking = $booking->room->bookings()
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })
            ->exists();

        if ($hasOverlappingBooking) {
            return redirect()->back()->with('error', 'Room is not available for the selected dates. Another booking conflicts.');
        }

        // Update status to confirmed
        $booking->update(['status' => 'confirmed']);

        return redirect()->back()->with('success', 'Booking accepted successfully! It will now appear in the main bookings list.');
    }

    public function cancel(Booking $booking, Request $request)
    {
        $this->authorize('edit booking');
        
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
            'cancellation_fee' => 'nullable|numeric|min:0'
        ]);
        
        if ($booking->cancel($request->cancellation_reason, $request->cancellation_fee ?? 0)) {
            return redirect()->back()
                ->with('success', 'Booking cancelled successfully!');
        }
        
        return redirect()->back()
            ->with('error', 'Cannot cancel booking at this time.');
    }

    public function recordPayment(Request $request, Booking $booking)
    {
        $this->authorize('edit booking');
        
        // Validate the request
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01|max:' . $booking->balance_due,
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_description' => 'nullable|string|max:500'
        ]);
        
        try {
            DB::beginTransaction();
            
            $paymentAmount = $request->payment_amount;
            $newPaidAmount = $booking->paid_amount + $paymentAmount;
            $newBalanceDue = $booking->total_amount - $newPaidAmount;
            
            // Calculate new payment status
            $paymentStatus = 'pending';
            if ($newPaidAmount > 0 && $newPaidAmount < $booking->total_amount) {
                $paymentStatus = 'partial';
            } elseif ($newPaidAmount >= $booking->total_amount) {
                $paymentStatus = 'paid';
            }
            
            // Update booking with new payment information
            $booking->update([
                'paid_amount' => $newPaidAmount,
                'balance_due' => $newBalanceDue,
                'payment_status' => $paymentStatus
            ]);
            
            // Create receipt for the payment
            $receipt = \App\Models\Receipt::create([
                'reference' => 'hotel_booking',
                'reference_type' => 'hotel_booking',
                'reference_number' => $booking->booking_number,
                'amount' => $paymentAmount,
                'date' => $request->payment_date,
                'description' => $request->payment_description ?: "Additional payment for booking #{$booking->booking_number}",
                'user_id' => auth()->id(),
                'bank_account_id' => $request->bank_account_id,
                'payee_type' => 'guest',
                'payee_id' => $booking->guest_id,
                'payee_name' => $booking->guest->first_name . ' ' . $booking->guest->last_name,
                'branch_id' => $booking->branch_id,
                'approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now()
            ]);

            // Create receipt item for the hotel booking payment
            $receipt->receiptItems()->create([
                'chart_account_id' => $this->getHotelRevenueAccountId(),
                'amount' => $paymentAmount,
                'description' => "Additional payment - Room {$booking->room->room_number} ({$booking->nights} nights)"
            ]);
            
            // Create GL transactions for the additional payment
            $this->createPaymentGLTransactions($booking, $paymentAmount, $request);
            
            DB::commit();
            
            return redirect()->route('bookings.show', $booking)
                ->with('success', "Payment of TSh " . number_format($paymentAmount, 0) . " recorded successfully!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }


    /**
     * Create receipt for paid booking
     */
    private function createBookingReceipt(Booking $booking, Request $request, $amount = null)
    {
        $receiptAmount = $amount ?? $booking->paid_amount;
        
        // Create a receipt for the payment
        $receipt = \App\Models\Receipt::create([
            'reference' => 'hotel_booking',
            'reference_type' => 'hotel_booking',
            'reference_number' => $booking->booking_number,
            'amount' => $receiptAmount,
            'date' => now(),
            'description' => "Payment received for hotel booking #{$booking->booking_number}",
            'user_id' => auth()->id(),
            'bank_account_id' => $request->bank_account_id,
            'payee_type' => 'guest',
            'payee_id' => $booking->guest_id,
            'payee_name' => $booking->guest->first_name . ' ' . $booking->guest->last_name,
            'branch_id' => $booking->branch_id,
            'approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);

        // Create receipt item for the hotel booking payment
        $receipt->receiptItems()->create([
            'chart_account_id' => $this->getHotelRevenueAccountId(),
            'amount' => $receiptAmount,
            'description' => "Hotel room payment - Room {$booking->room->room_number} ({$booking->nights} nights)"
        ]);

        return $receipt;
    }

    /**
     * Get the hotel revenue account ID
     */
    private function getHotelRevenueAccountId()
    {
        // Try to find hotel revenue account, create a default one if not found
        $account = \App\Models\ChartAccount::where('account_name', 'Hotel Room Revenue')->first();
        
        if (!$account) {
            // Create a default hotel revenue account
            $account = \App\Models\ChartAccount::create([
                'account_name' => 'Hotel Room Revenue',
                'account_code' => '4001',
            ]);
        }
        
        return $account->id;
    }

    /**
     * Get the hotel discount expense account ID
     */
    private function getHotelDiscountExpenseAccountId()
    {
        $fromSetting = \App\Models\SystemSetting::where('key', 'hotel_discount_expense_account_id')->value('value');
        if ($fromSetting) {
            return (int) $fromSetting;
        }
        // Fallback to seeded Discount Expense account by ID (172)
        $byId = \App\Models\ChartAccount::where('id', 172)->value('id');
        if ($byId) return (int) $byId;
        // Last resort: create (without forcing a specific ID)
        return \App\Models\ChartAccount::create([
            'account_name' => 'Discount Expense',
            'account_code' => '5307',
        ])->id;
    }

    /**
     * Create GL transactions for booking
     */
    private function createBookingGLTransactions(Booking $booking)
    {
        // Calculate gross and discount
        $grossAmount = $booking->room_rate * $booking->nights;
        $discountAmount = $booking->discount_amount ?? 0;
        $netAmount = max(0, $grossAmount - $discountAmount);

        // 1. Credit: Hotel Room Revenue (gross)
        \App\Models\GlTransaction::create([
            'chart_account_id' => $this->getHotelRevenueAccountId(),
            'amount' => $grossAmount,
            'nature' => 'credit',
            'transaction_type' => 'hotel_booking',
            'transaction_id' => $booking->id,
            'description' => "Room revenue for booking #{$booking->booking_number}",
            'date' => $booking->check_in,
            'branch_id' => $booking->branch_id,
            'user_id' => auth()->id()
        ]);
        
        // 2. Debit: Discount Expense (if any)
        if ($discountAmount > 0) {
            \App\Models\GlTransaction::create([
                'chart_account_id' => $this->getHotelDiscountExpenseAccountId(),
                'amount' => $discountAmount,
                'nature' => 'debit',
                'transaction_type' => 'hotel_booking',
                'transaction_id' => $booking->id,
                'description' => "Discount given for booking #{$booking->booking_number}",
                'date' => $booking->check_in,
                'branch_id' => $booking->branch_id,
                'user_id' => auth()->id()
            ]);
        }

        // 3. Debit: Accounts Receivable (for net unpaid amount)
        if ($booking->balance_due > 0) {
            \App\Models\GlTransaction::create([
                'chart_account_id' => $this->getAccountsReceivableAccountId(),
                'amount' => $booking->balance_due,
                'nature' => 'debit',
                'transaction_type' => 'hotel_booking',
                'transaction_id' => $booking->id,
                'description' => "Receivable (net) for booking #{$booking->booking_number}",
                'date' => $booking->check_in,
                'branch_id' => $booking->branch_id,
                'user_id' => auth()->id()
            ]);
        }
        
        // 4. Debit: Cash/Bank (for paid amount)
        if ($booking->paid_amount > 0) {
            \App\Models\GlTransaction::create([
                'chart_account_id' => $this->getCashAccountId($booking),
                'amount' => $booking->paid_amount,
                'nature' => 'debit',
                'transaction_type' => 'hotel_booking',
                'transaction_id' => $booking->id,
                'description' => "Payment received (net) for booking #{$booking->booking_number}",
                'date' => $booking->check_in,
                'branch_id' => $booking->branch_id,
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * Get the accounts receivable account ID
     */
    private function getAccountsReceivableAccountId()
    {
        // Try to find accounts receivable account, create a default one if not found
        $account = \App\Models\ChartAccount::where('account_name', 'Trade Receivables')->first();
        
        if (!$account) {
            // Create a default accounts receivable account
            $account = \App\Models\ChartAccount::create([
                'account_name' => 'Accounts Receivable',
                'account_code' => '1200',
            ]);
        }
        
        return $account->id;
    }

    /**
     * Get the cash account ID for the booking
     */
    private function getCashAccountId(Booking $booking)
    {
        // If there's a receipt with bank account, use that bank's GL account
        $receipt = \App\Models\Receipt::where('reference_type', 'hotel_booking')
            ->where('reference_number', $booking->booking_number)
            ->first();
            
        if ($receipt && $receipt->bank_account_id) {
            // Use the bank account's GL account
            $bankAccount = \App\Models\BankAccount::find($receipt->bank_account_id);
            if ($bankAccount && $bankAccount->chart_account_id) {
                return $bankAccount->chart_account_id;
            }
        }
        
        // Default to cash account
        $account = \App\Models\ChartAccount::where('account_name', 'Cash on Hand')->first();
        
        if (!$account) {
            // Create a default cash account
            $account = \App\Models\ChartAccount::create([
                'account_name' => 'Cash',
                'account_code' => '1000',
            ]);
        }
        
        return $account->id;
    }

    /**
     * Create GL transactions for additional payment
     */
    private function createPaymentGLTransactions(Booking $booking, $paymentAmount, Request $request)
    {
        // 1. Debit: Accounts Receivable (reduce receivable by payment amount)
        \App\Models\GlTransaction::create([
            'chart_account_id' => $this->getAccountsReceivableAccountId(),
            'amount' => $paymentAmount,
            'nature' => 'credit', // Credit to reduce receivable
            'transaction_type' => 'hotel_payment',
            'transaction_id' => $booking->id,
            'description' => "Payment received for booking #{$booking->booking_number}",
            'date' => now(),
            'branch_id' => $booking->branch_id,
            'user_id' => auth()->id()
        ]);
        
        // 2. Debit: Cash/Bank (increase cash/bank by payment amount)
        \App\Models\GlTransaction::create([
            'chart_account_id' => $this->getCashAccountId($booking),
            'amount' => $paymentAmount,
            'nature' => 'debit', // Debit to increase cash/bank
            'transaction_type' => 'hotel_payment',
            'transaction_id' => $booking->id,
            'description' => "Payment received for booking #{$booking->booking_number}",
            'date' => now(),
            'branch_id' => $booking->branch_id,
            'user_id' => auth()->id()
        ]);
    }

    /**
     * Edit receipt for booking
     */
    public function editReceipt($encodedId)
    {
        try {
            // Decode the Hashids-encoded receipt ID
            $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;

            if (!$decodedId) {
                abort(404, 'Invalid receipt ID.');
            }

            // Find the receipt
            $receipt = \App\Models\Receipt::with(['bankAccount', 'customer'])->findOrFail($decodedId);

            // Check if this receipt is related to a booking
            $booking = \App\Models\Hotel\Booking::where('booking_number', $receipt->reference_number)->first();

            if (!$booking) {
                abort(404, 'Receipt not related to any booking.');
            }

            $bankAccounts = \App\Models\BankAccount::orderBy('name')->get();

            return view('hotel.bookings.edit-receipt', compact('receipt', 'booking', 'bankAccounts'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to load receipt: ' . $e->getMessage()]);
        }
    }

    /**
     * Update receipt for booking
     */
    public function updateReceipt(Request $request, $encodedId)
    {
        try {
            // Decode the Hashids-encoded receipt ID
            $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;

            if (!$decodedId) {
                abort(404, 'Invalid receipt ID.');
            }

            $request->validate([
                'amount' => 'required|numeric|min:0',
                'date' => 'required|date',
                'bank_account_id' => 'required|exists:bank_accounts,id',
                'description' => 'nullable|string|max:1000'
            ]);

            $receipt = \App\Models\Receipt::findOrFail($decodedId);

            // Check if this receipt is related to a booking
            $booking = \App\Models\Hotel\Booking::where('booking_number', $receipt->reference_number)->first();

            if (!$booking) {
                abort(404, 'Receipt not related to any booking.');
            }

            \DB::transaction(function () use ($request, $receipt, $booking) {
                $oldAmount = $receipt->amount;
                $newAmount = $request->amount;

                // Update receipt
                $receipt->update([
                    'amount' => $newAmount,
                    'date' => $request->date,
                    'description' => $request->description,
                    'bank_account_id' => $request->bank_account_id,
                ]);

                // Update receipt items
                $receipt->receiptItems()->update([
                    'amount' => $newAmount,
                    'description' => $request->description,
                ]);

                // Remove old GL transactions
                $receipt->glTransactions()->delete();

                $user = \Auth::user();

                // Create new GL transactions
                if ($newAmount > 0) {
                    // Credit: Hotel Room Revenue
                    \App\Models\GlTransaction::create([
                        'chart_account_id' => $this->getHotelRevenueAccountId(),
                        'amount' => $newAmount,
                        'nature' => 'credit',
                        'transaction_type' => 'hotel_booking',
                        'transaction_id' => $booking->id,
                        'description' => "Room revenue for booking #{$booking->booking_number}",
                        'date' => $request->date,
                        'branch_id' => $booking->branch_id,
                        'user_id' => $user->id
                    ]);

                    // Debit: Cash/Bank
                    \App\Models\GlTransaction::create([
                        'chart_account_id' => $this->getCashAccountId($booking),
                        'amount' => $newAmount,
                        'nature' => 'debit',
                        'transaction_type' => 'hotel_booking',
                        'transaction_id' => $booking->id,
                        'description' => "Payment received for booking #{$booking->booking_number}",
                        'date' => $request->date,
                        'branch_id' => $booking->branch_id,
                        'user_id' => $user->id
                    ]);
                }

                // Update booking paid amount
                $booking->update(['paid_amount' => $newAmount]);
            });

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Receipt updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update receipt: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Print receipt for booking
     */
    public function printReceipt($encodedId)
    {
        try {
            // Decode the Hashids-encoded receipt ID
            $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;

            if (!$decodedId) {
                abort(404, 'Invalid receipt ID.');
            }

            // Find the receipt with related data
            $receipt = \App\Models\Receipt::with([
                'bankAccount', 
                'customer', 
                'receiptItems.chartAccount',
                'user',
                'branch'
            ])->findOrFail($decodedId);

            // Check if this receipt is related to a booking
            $booking = \App\Models\Hotel\Booking::with(['guest', 'room', 'company', 'branch'])
                ->where('booking_number', $receipt->reference_number)
                ->first();

            if (!$booking) {
                abort(404, 'Receipt not related to any booking.');
            }

            // Use view similar to sales invoice receipt
            return view('hotel.bookings.receipt', compact('booking', 'receipt'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to print receipt: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Check availability for a room date range
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'exclude_booking_id' => 'nullable|integer'
        ]);

        $room = Room::findOrFail($request->room_id);
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $excludeId = $request->exclude_booking_id;

        $available = $excludeId
            ? $room->isAvailableForDateRange($checkIn, $checkOut, $excludeId)
            : $room->isAvailableForDates($checkIn, $checkOut);

        if ($available) {
            return response()->json(['available' => true]);
        }

        $conflict = $room->bookings()
            ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })
            ->orderBy('check_in')
            ->first();

        return response()->json([
            'available' => false,
            'message' => $conflict ? sprintf(
                'Selected range %s → %s overlaps existing booking %s → %s.',
                $checkIn->format('Y-m-d'),
                $checkOut->format('Y-m-d'),
                $conflict->check_in->format('Y-m-d'),
                $conflict->check_out->format('Y-m-d')
            ) : 'Room is not available for the selected dates.'
        ]);
    }

    /**
     * AJAX: Get available rooms for selected date range
     */
    public function availableRooms(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $branch_id = session('branch_id') ?? Auth::user()->branch_id ?? 1;

        $rooms = Room::with('property')
            ->where('branch_id', $branch_id)
            ->whereNotIn('status', ['maintenance', 'out_of_order'])
            ->orderBy('room_number')
            ->get()
            ->filter(function ($room) use ($checkIn, $checkOut) {
                return $room->isAvailableForDates($checkIn, $checkOut);
        })->map(function ($room) {
            return [
                'id' => $room->id,
                'label' => ($room->full_room_name) . ' - ' . ($room->property->name ?? 'No Property'),
                'rate' => $room->rate_per_night,
                'capacity' => $room->capacity,
                'type' => $room->room_type,
            ];
        })->values();

        return response()->json(['rooms' => $rooms]);
    }

    /**
     * API: Get available rooms with full details for date range
     * GET /api/bookings/available-rooms?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD&page=1&per_page=12
     */
    public function availableRoomsApi(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $branch_id = $request->get('branch_id', 1); // Default to branch 1 for public API

        // Get pagination parameters
        $perPage = $request->get('per_page', 12);
        $page = $request->get('page', 1);

        $query = Room::with('property')
            ->where('branch_id', $branch_id)
            ->whereNotIn('status', ['maintenance', 'out_of_order'])
            ->orderBy('room_number');

        // Get all rooms (don't filter by availability - we'll mark them as booked)
        $allRooms = $query->get();

        // Paginate manually
        $total = $allRooms->count();
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedRooms = $allRooms->slice($offset, $perPage);

        $roomsData = $paginatedRooms->map(function ($room) use ($checkIn, $checkOut, $branch_id) {
            // Check if room is booked for the search dates
            // Room is booked when status is: online_booking, confirmed, checked_in
            // Room is NOT booked when status is: pending
            // Also ensure we're checking bookings for the same branch
            $hasOverlappingBooking = \App\Models\Hotel\Booking::where('room_id', $room->id)
                ->where('branch_id', $branch_id)
                ->where('status', '!=', 'cancelled') // Exclude cancelled
                ->whereIn('status', ['confirmed', 'checked_in', 'online_booking']) // Exclude pending
                ->where(function ($q) use ($checkIn, $checkOut) {
                    // Overlap detection: booking overlaps if it starts before selected range ends 
                    // AND ends after selected range starts
                    // Use date comparison directly (database columns are cast as 'date')
                    $q->where('check_in', '<', $checkOut)
                      ->where('check_out', '>', $checkIn);
                })
                ->exists();
            
            $isBookedForDates = $hasOverlappingBooking;

            // Determine actual status based on room status and bookings
            // If room has overlapping bookings (online_booking, confirmed, checked_in), mark as booked
            if ($isBookedForDates) {
                $actualStatus = 'booked';
            } else {
                // If no overlapping bookings, use room's base status
                // Map maintenance/out_of_order to themselves, otherwise show as available
                if (in_array($room->status, ['maintenance', 'out_of_order'])) {
                    $actualStatus = $room->status;
                } else {
                    $actualStatus = 'available';
                }
            }

            return [
                'id' => $room->id,
                'hashid' => $room->hash_id ?? null,
                'name' => $room->room_name ?? $room->room_number,
                'room_number' => $room->room_number,
                'type' => $room->room_type,
                'price' => (float) $room->rate_per_night,
                'max_adults' => (int) $room->capacity,
                'max_children' => (int) ($room->max_children ?? 0),
                'status' => $this->mapRoomStatus($actualStatus),
                'description' => $room->description,
                'amenities' => $this->getRoomAmenities($room),
                'images' => $this->getRoomImages($room),
                'created_at' => $room->created_at?->toISOString(),
                'updated_at' => $room->updated_at?->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $roomsData,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Map Laravel room status to API status
     */
    private function mapRoomStatus($status)
    {
        $statusMap = [
            'available' => 'available',
            'booked' => 'booked',
            'occupied' => 'booked',
            'maintenance' => 'maintenance',
            'out_of_order' => 'out_of_order',
        ];

        return $statusMap[$status] ?? 'available';
    }

    /**
     * Get amenities array from room model
     */
    private function getRoomAmenities($room)
    {
        $amenities = [];
        
        if (isset($room->has_wifi) && $room->has_wifi) {
            $amenities[] = 'Free WiFi';
        }
        if (isset($room->has_ac) && $room->has_ac) {
            $amenities[] = 'Air Conditioning';
        }
        if (isset($room->has_tv) && $room->has_tv) {
            $amenities[] = 'Smart TV';
        }
        if (isset($room->has_balcony) && $room->has_balcony) {
            $amenities[] = 'Balcony';
        }
        if (isset($room->has_kitchen) && $room->has_kitchen) {
            $amenities[] = 'Kitchen';
        }
        
        // Also check amenities array if it exists
        if (is_array($room->amenities)) {
            foreach ($room->amenities as $amenity) {
                $amenityMap = [
                    'wifi' => 'Free WiFi',
                    'ac' => 'Air Conditioning',
                    'tv' => 'Smart TV',
                    'minibar' => 'Mini Bar',
                    'balcony' => 'Balcony',
                    'kitchen' => 'Kitchen',
                    'ocean_view' => 'Ocean View',
                    'city_view' => 'City View',
                ];
                
                if (isset($amenityMap[$amenity]) && !in_array($amenityMap[$amenity], $amenities)) {
                    $amenities[] = $amenityMap[$amenity];
                }
            }
        }
        
        return array_unique($amenities);
    }

    /**
     * Get images array from room model
     */
    private function getRoomImages($room)
    {
        // If images is already an array, return it
        if (isset($room->images) && is_array($room->images)) {
            return $room->images;
        }
        
        // If images is stored as JSON string, decode it
        if (isset($room->images) && is_string($room->images)) {
            $decoded = json_decode($room->images, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }
        
        // Return placeholder image if no images
        return ['https://via.placeholder.com/800x600?text=Room+Image'];
    }

    /**
     * Delete receipt for booking
     */
    public function deleteReceipt($encodedId)
    {
        try {
            // Decode the Hashids-encoded receipt ID
            $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;

            if (!$decodedId) {
                abort(404, 'Invalid receipt ID.');
            }

            $receipt = \App\Models\Receipt::findOrFail($decodedId);

            // Check if this receipt is related to a booking
            $booking = \App\Models\Hotel\Booking::where('booking_number', $receipt->reference_number)->first();

            if (!$booking) {
                abort(404, 'Receipt not related to any booking.');
            }

            \DB::transaction(function () use ($receipt, $booking) {
                // Remove GL transactions
                $receipt->glTransactions()->delete();

                // Delete receipt items
                $receipt->receiptItems()->delete();

                // Delete receipt
                $receipt->delete();

                // Update booking paid amount
                $booking->update(['paid_amount' => 0]);
            });

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Receipt deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete receipt: ' . $e->getMessage()]);
        }
    }

    /**
     * Export booking details as PDF
     */
    public function exportPdf(Booking $booking)
    {
        $this->authorize('view booking details');
        
        $booking->load([
            'room.property', 
            'guest', 
            'createdBy',
            'glTransactions.chartAccount',
            'paymentGlTransactions.chartAccount',
            'receipts.receiptItems.chartAccount',
            'receipts.bankAccount',
            'branch',
            'company'
        ]);
        
        // Get bank accounts for payment methods (like sales invoice)
        $bankAccounts = \App\Models\BankAccount::all();

        // Terms and Conditions from hotel settings (shown in PDF footer)
        $termsAndConditions = (string) \App\Models\SystemSetting::getValue('hotel_terms_and_conditions', '');
        
        // Apply paper size/orientation from settings (like sales invoice)
        $pageSize = strtoupper((string) (\App\Models\SystemSetting::getValue('document_page_size', 'A4')));
        $orientation = strtolower((string) (\App\Models\SystemSetting::getValue('document_orientation', 'portrait')));
        
        // Get margin settings
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '15mm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '15mm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '15mm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '15mm');
        
        // Convert margin strings to numeric values in mm
        $convertToMm = function($value) {
            $value = trim(strtolower($value));
            if (strpos($value, 'mm') !== false) {
                return (float) str_replace('mm', '', $value);
            } elseif (strpos($value, 'cm') !== false) {
                return (float) str_replace('cm', '', $value) * 10;
            } elseif (strpos($value, 'in') !== false) {
                return (float) str_replace('in', '', $value) * 25.4;
            }
            return (float) $value;
        };
        
        $marginTop = $convertToMm($marginTopStr);
        $marginRight = $convertToMm($marginRightStr);
        $marginBottom = $convertToMm($marginBottomStr);
        $marginLeft = $convertToMm($marginLeftStr);
        
        try {
            $pdf = \PDF::loadView('hotel.bookings.export-pdf', compact('booking', 'bankAccounts', 'termsAndConditions'));
            $pdf->setPaper($pageSize, $orientation);
            
            // Set margins programmatically using setOptions (dompdf expects numeric values in mm)
            $pdf->setOptions([
                'margin-top' => $marginTop,
                'margin-right' => $marginRight,
                'margin-bottom' => $marginBottom,
                'margin-left' => $marginLeft,
            ]);
            
            // Generate filename with booking number
            $filename = 'Booking_' . $booking->booking_number . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Booking PDF generation error: ' . $e->getMessage());
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * API: Create online booking from web portal
     * POST /api/bookings
     */
    public function createOnlineBookingApi(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        // Get authenticated guest from Sanctum token
        $guest = $request->user();
        
        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login to create a booking.',
            ], 401);
        }

        DB::beginTransaction();
        
        try {
            // Check room availability
            $room = Room::findOrFail($request->room_id);
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            
            // Check if room is available (excluding online_booking status as they can be cancelled)
            $hasOverlappingBooking = $room->bookings()
                ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                ->where(function ($q) use ($checkIn, $checkOut) {
                    $q->where('check_in', '<', $checkOut)
                      ->where('check_out', '>', $checkIn);
                })
                ->exists();

            if ($hasOverlappingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room is not available for the selected dates.',
                ], 400);
            }

            // Calculate nights and total amount
            $nights = $checkIn->diffInDays($checkOut);
            $roomRate = $room->rate_per_night;
            $totalAmount = $roomRate * $nights;

            // Generate unique booking number
            $booking = new Booking();
            $bookingNumber = $booking->generateBookingNumber();
            $branch_id = $room->branch_id ?? 1;
            $company_id = $room->company_id ?? 1;

            $booking = Booking::create([
                'booking_number' => $bookingNumber,
                'room_id' => $request->room_id,
                'guest_id' => $guest->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $request->adults,
                'children' => $request->children ?? 0,
                'nights' => $nights,
                'room_rate' => $roomRate,
                'total_amount' => $totalAmount,
                'discount_amount' => 0,
                'paid_amount' => 0,
                'balance_due' => $totalAmount,
                'status' => 'online_booking', // Set status as online_booking
                'payment_status' => 'pending',
                'booking_source' => 'online',
                'special_requests' => $request->special_requests,
                'branch_id' => $branch_id,
                'company_id' => $company_id,
                'created_by' => 1, // System user for online bookings
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully. Please confirm payment within 2 hours.',
                'data' => [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'check_in' => $booking->check_in->format('Y-m-d'),
                    'check_out' => $booking->check_out->format('Y-m-d'),
                    'adults' => $booking->adults,
                    'children' => $booking->children,
                    'total_amount' => (float) $booking->total_amount,
                    'total_price' => (float) $booking->total_amount, // Alias for frontend compatibility
                    'status' => $booking->status,
                    'created_at' => $booking->created_at->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Online booking creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get authenticated guest's bookings
     * GET /api/bookings
     */
    public function getMyBookingsApi(Request $request)
    {
        $guest = $request->user();
        
        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Filter by branch if provided
        $query = Booking::with('room')
            ->where('guest_id', $guest->id);
            
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $bookings = $query->with('branch')->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                $room = $booking->room;
                return [
                    'id' => $booking->id,
                    'hashid' => $booking->hash_id,
                    'booking_number' => $booking->booking_number,
                    'room' => $room ? [
                        'id' => $room->id,
                        'hashid' => $room->hash_id,
                        'name' => $room->room_name ?? $room->room_number,
                        'room_number' => $room->room_number,
                        'type' => $room->room_type,
                        'description' => $room->description,
                        'amenities' => $this->getRoomAmenities($room),
                        'images' => $this->getRoomImages($room),
                    ] : null,
                    'check_in' => $booking->check_in->format('Y-m-d'),
                    'check_out' => $booking->check_out->format('Y-m-d'),
                    'adults' => $booking->adults,
                    'children' => $booking->children,
                    'total_amount' => (float) $booking->total_amount,
                    'total_price' => (float) $booking->total_amount, // Alias for frontend compatibility
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                    'created_at' => $booking->created_at->toISOString(),
                    'branch' => $booking->branch ? [
                        'id' => $booking->branch->id,
                        'name' => $booking->branch->name ?? $booking->branch->branch_name,
                        'address' => $booking->branch->address ?? $booking->branch->location,
                        'phone' => $booking->branch->phone,
                        'email' => $booking->branch->email,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * API: Get a single booking for authenticated guest
     * GET /api/bookings/{booking}
     */
    public function getMyBookingByIdApi(Request $request, $bookingId)
    {
        $guest = $request->user();

        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Try to decode hashid if it's not numeric
        $decodedId = is_numeric($bookingId) ? $bookingId : \App\Helpers\HashIdHelper::decode($bookingId);
        
        if (!$decodedId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid booking ID',
            ], 404);
        }

        $booking = Booking::with(['room', 'branch'])
            ->where('guest_id', $guest->id)
            ->find($decodedId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        $room = $booking->room;

        // Get payment history (receipts)
        $receipts = $booking->receipts()->with('bankAccount')->orderBy('date', 'desc')->get();
        $paymentHistory = $receipts->map(function ($receipt) {
            return [
                'id' => $receipt->id,
                'date' => $receipt->date->format('Y-m-d'),
                'amount' => (float) $receipt->amount,
                'type' => $receipt->bank_account_id ? 'Bank' : 'Cash',
                'bank_account' => $receipt->bankAccount ? $receipt->bankAccount->name : null,
                'description' => $receipt->description,
            ];
        });

        $data = [
            'id' => $booking->id,
            'hashid' => $booking->hash_id,
            'booking_number' => $booking->booking_number,
            'room_id' => $booking->room_id,
            'guest_id' => $booking->guest_id,
            'check_in' => $booking->check_in->format('Y-m-d'),
            'check_out' => $booking->check_out->format('Y-m-d'),
            'adults' => $booking->adults,
            'children' => $booking->children,
            'nights' => $booking->nights ?? $booking->check_in->diffInDays($booking->check_out),
            'room_rate' => (float) ($booking->room_rate ?? 0),
            'total_amount' => (float) $booking->total_amount,
            'total_price' => (float) $booking->total_amount,
            'paid_amount' => (float) ($booking->paid_amount ?? 0),
            'balance_due' => (float) ($booking->balance_due ?? $booking->total_amount),
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'special_requests' => $booking->special_requests,
            'created_at' => $booking->created_at->toISOString(),
            'updated_at' => $booking->updated_at->toISOString(),
            'room' => $room ? [
                'id' => $room->id,
                'hashid' => $room->hash_id,
                'name' => $room->room_name ?? $room->room_number,
                'room_number' => $room->room_number,
                'type' => $room->room_type,
                'description' => $room->description,
                'amenities' => $this->getRoomAmenities($room),
                'images' => $this->getRoomImages($room),
            ] : null,
            'payment_history' => $paymentHistory,
            'branch' => $booking->branch ? [
                'id' => $booking->branch->id,
                'name' => $booking->branch->name ?? $booking->branch->branch_name,
                'address' => $booking->branch->address ?? $booking->branch->location,
                'phone' => $booking->branch->phone,
                'email' => $booking->branch->email,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Download receipt PDF for booking
     * GET /api/bookings/{booking}/receipt
     */
    public function downloadReceiptApi(Request $request, $bookingId)
    {
        $guest = $request->user();

        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Try to decode hashid if it's not numeric
        $decodedId = is_numeric($bookingId) ? $bookingId : \App\Helpers\HashIdHelper::decode($bookingId);
        
        if (!$decodedId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid booking ID',
            ], 404);
        }

        $booking = Booking::where('guest_id', $guest->id)
            ->find($decodedId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        try {
            // Load booking relationships
            $booking->load(['guest', 'room', 'company', 'branch']);

            // Get bank accounts for payment methods
            $bankAccounts = \App\Models\BankAccount::with('chartAccount')
                ->whereNotNull('account_number')
                ->where('account_number', '!=', '')
                ->orderBy('name')
                ->get()
                ->map(function ($account) {
                    // Extract bank name from chart account name
                    $bankName = null;
                    if ($account->chartAccount && $account->chartAccount->account_name) {
                        $chartAccountName = $account->chartAccount->account_name;
                        if (strpos($chartAccountName, ' - ') !== false) {
                            $bankName = trim(explode(' - ', $chartAccountName)[0]);
                        } else {
                            $bankName = $chartAccountName;
                        }
                    }
                    if (!$bankName) {
                        $bankName = $account->name;
                    }

                    return [
                        'name' => $account->name,
                        'bank_name' => $bankName,
                        'account_number' => $account->account_number,
                        'currency' => $account->currency ?? 'TZS',
                    ];
                });

            // Get receipt if exists (optional)
            $receipt = $booking->receipts()->first();
            if ($receipt) {
                $receipt->load(['bankAccount', 'customer', 'receiptItems.chartAccount', 'user', 'branch']);
            }

            // Generate PDF - use booking confirmation format
            $pdf = \PDF::loadView('hotel.bookings.receipt', compact('booking', 'receipt', 'bankAccounts'));
            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'Booking_Confirmation_' . ($booking->booking_number ?? 'BK' . $booking->id) . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Receipt PDF generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate receipt PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Cancel a booking for authenticated guest
     * POST /api/bookings/{booking}/cancel
     */
    public function cancelBookingApi(Request $request, $bookingId)
    {
        $guest = $request->user();

        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Try to decode hashid if it's not numeric
        $decodedId = is_numeric($bookingId) ? $bookingId : \App\Helpers\HashIdHelper::decode($bookingId);
        
        if (!$decodedId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid booking ID',
            ], 404);
        }

        $booking = Booking::where('guest_id', $guest->id)
            ->find($decodedId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // Only allow cancellation of pending or online_booking status
        if (!in_array($booking->status, ['pending', 'online_booking'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or online bookings can be cancelled',
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // Determine payment_status based on whether payment was made
            // If there was a payment, mark as refunded; otherwise keep as pending
            $paymentStatus = $booking->payment_status;
            if ($booking->paid_amount > 0) {
                $paymentStatus = 'refunded';
            } else {
                $paymentStatus = 'pending';
            }

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => $paymentStatus,
                'cancellation_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking cancellation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update web portal settings
     */
    public function updateWebPortalSettings(Request $request)
    {
        $this->authorize('view bookings');
        
        $user = Auth::user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'enable_booking_portal' => 'nullable',
            'require_admin_approval' => 'nullable',
            'online_booking_expiry_hours' => 'required|integer|min:1|max:168',
            'booking_availability_hours' => 'required|integer|min:1|max:168',
            'enable_dynamic_pricing' => 'nullable',
            'weekend_rate_multiplier' => 'nullable|numeric|min:0.5|max:3',
            'enable_promo_codes' => 'nullable',
            'enable_captcha' => 'nullable',
            'portal_notification_email' => 'nullable|email',
            'portal_notification_sms' => 'nullable|string|max:50',
            'enable_email_verification' => 'nullable',
            'portal_tax_rate' => 'nullable|numeric|min:0|max:100',
            'portal_terms_conditions' => 'nullable|string|max:5000',
        ]);

        // Update settings using HotelPortalSetting
        foreach ($validated as $key => $value) {
            if (in_array($key, ['enable_booking_portal', 'require_admin_approval', 'enable_dynamic_pricing', 'enable_promo_codes', 'enable_captcha', 'enable_email_verification'])) {
                $boolValue = $value ? '1' : '0';
                \App\Models\Hotel\HotelPortalSetting::setSetting($companyId, $key, $boolValue, $user->id);
            } else {
                \App\Models\Hotel\HotelPortalSetting::setSetting($companyId, $key, $value ?? '', $user->id);
            }
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Web portal settings updated successfully.');
    }

    /**
     * Clean up expired online bookings (older than configured hours)
     * 
     * @param int $branch_id
     * @return void
     */
    private function cleanupExpiredOnlineBookings($branch_id)
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;
            
            // Get expiry hours from settings
            $expiryHours = (int) \App\Models\Hotel\HotelPortalSetting::getSetting(
                $companyId,
                'online_booking_expiry_hours',
                2
            );
            
            // Get online bookings created more than expiry hours ago
            $expiredBookings = Booking::where('status', 'online_booking')
                ->where('branch_id', $branch_id)
                ->where('company_id', $companyId)
                ->where('created_at', '<=', Carbon::now()->subHours($expiryHours))
                ->get();
            
            if ($expiredBookings->isEmpty()) {
                return;
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($expiredBookings as $booking) {
                    $bookingNumber = $booking->booking_number;
                    $bookingId = $booking->id;
                    
                    // Delete the booking
                    $booking->delete();
                    
                    Log::info('Online booking auto-deleted from bookings page', [
                        'booking_id' => $bookingId,
                        'booking_number' => $bookingNumber,
                        'company_id' => $companyId,
                        'branch_id' => $branch_id,
                        'expiry_hours' => $expiryHours,
                        'created_at' => $booking->created_at,
                    ]);
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error deleting expired online bookings from page', [
                    'company_id' => $companyId,
                    'branch_id' => $branch_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in cleanupExpiredOnlineBookings', [
                'error' => $e->getMessage(),
            ]);
        }
    }

}
