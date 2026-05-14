'use client';

import React, { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { roomsAPI } from '@/lib/api/rooms';
import { bookingsAPI, BookingData } from '@/lib/api/bookings';
import { bankAccountsAPI, BankAccount } from '@/lib/api/bankAccounts';
import Swal from 'sweetalert2';
import Link from 'next/link';

export default function BookingPage() {
  const { id } = useParams();
  const router = useRouter();
  const { isAuthenticated, user } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';

  const [room, setRoom] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    checkIn: '',
    checkOut: '',
    adults: '1',
    children: '0',
    specialRequests: '',
  });

  useEffect(() => {
    if (!isAuthenticated) {
      // Save booking data before redirecting
      if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const checkIn = params.get('checkIn');
        const checkOut = params.get('checkOut');
        const adults = params.get('adults');
        const children = params.get('children');
        
        if (checkIn && checkOut) {
          const bookingData = {
            room_id: id,
            checkIn,
            checkOut,
            adults: adults || '2',
            children: children || '0',
          };
          sessionStorage.setItem('pending_booking', JSON.stringify(bookingData));
        }
        // Save current page URL
        sessionStorage.setItem('redirect_url', window.location.pathname + window.location.search);
      }
      router.push('/login');
      return;
    }

    const fetchRoom = async () => {
      try {
        const roomData = await roomsAPI.getRoomById(id as string);
        setRoom(roomData);

        let checkInParam = '';
        let checkOutParam = '';
        let adultsParam = '';
        let childrenParam = '';

        // First check if there's saved booking data
        const savedBooking = sessionStorage.getItem('pending_booking');
        if (savedBooking) {
          try {
            const booking = JSON.parse(savedBooking);
            // Match by hashid (if URL uses hashid) or numeric ID (if URL uses numeric ID)
            const idMatches = 
              booking.room_hashid === id || 
              booking.room_id === id || 
              booking.room_id === parseInt(id as string) ||
              String(booking.room_id) === String(id);
            
            if (idMatches) {
              checkInParam = booking.checkIn;
              checkOutParam = booking.checkOut;
              adultsParam = booking.adults;
              childrenParam = booking.children;
              // Clear saved booking data after using it
              sessionStorage.removeItem('pending_booking');
            }
          } catch (e) {
            console.error('Failed to parse saved booking:', e);
          }
        }

        // Fallback to URL params or defaults
        if (!checkInParam || !checkOutParam) {
          if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            checkInParam = params.get('checkIn') || '';
            checkOutParam = params.get('checkOut') || '';
            adultsParam = params.get('adults') || '';
            childrenParam = params.get('children') || '';
          }
        }

        // Fallback to default dates if not provided from search
        if (!checkInParam || !checkOutParam) {
          const today = new Date();
          const checkOut = new Date(today);
          checkOut.setDate(checkOut.getDate() + 3);
          checkInParam = today.toISOString().split('T')[0];
          checkOutParam = checkOut.toISOString().split('T')[0];
        }

        setFormData((prev) => ({
          ...prev,
          checkIn: checkInParam,
          checkOut: checkOutParam,
          adults: adultsParam || prev.adults,
          children: childrenParam || prev.children,
        }));
      } catch (err: any) {
        setError(err.message || (isEn ? 'Failed to load room' : 'Imeshindwa kupakia chumba'));
      } finally {
        setIsLoading(false);
      }
    };

    fetchRoom();
  }, [id, isAuthenticated, router, isEn]);

  const calculateNights = () => {
    if (!formData.checkIn || !formData.checkOut) return 0;
    const checkIn = new Date(formData.checkIn);
    const checkOut = new Date(formData.checkOut);
    const diffTime = Math.abs(checkOut.getTime() - checkIn.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  const calculateTotal = () => {
    if (!room) return 0;
    const nights = calculateNights();
    return room.price * nights;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      if (!room || !room.id) {
        throw new Error(isEn ? 'Room information is missing' : 'Taarifa za chumba hazipo');
      }

      const bookingData: BookingData = {
        room_id: room.id, // Use room.id from fetched room data
        check_in: formData.checkIn,
        check_out: formData.checkOut,
        adults: parseInt(formData.adults),
        children: parseInt(formData.children),
        special_requests: formData.specialRequests || undefined,
      };

      const booking = await bookingsAPI.createBooking(bookingData);
      
      // Fetch bank accounts for payment
      let bankAccounts: BankAccount[] = [];
      try {
        bankAccounts = await bankAccountsAPI.getBankAccounts();
      } catch (error) {
        console.error('Failed to fetch bank accounts:', error);
      }
      
      // Format booking details
      const nights = calculateNights();
      const total = calculateTotal();
      const checkInDate = new Date(formData.checkIn).toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
      const checkOutDate = new Date(formData.checkOut).toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
      
      // Build booking details HTML
      let bookingDetailsHtml = `
        <div style="text-align: left; margin: 20px 0;">
          <h3 style="color: #0d171b; margin-bottom: 15px; font-size: 18px; font-weight: bold;">
            ${isEn ? 'Booking Details' : 'Maelezo ya Kuomba'}
          </h3>
          <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin: 8px 0;"><strong>${isEn ? 'Booking Number' : 'Nambari ya Kuomba'}:</strong> ${booking.booking_number || `#${booking.id}`}</p>
            <p style="margin: 8px 0;"><strong>${isEn ? 'Room' : 'Chumba'}:</strong> ${room.name}${room.room_number ? ` (${room.room_number})` : ''}</p>
            <p style="margin: 8px 0;"><strong>${isEn ? 'Check-in' : 'Tarehe ya Kuingia'}:</strong> ${checkInDate}</p>
            <p style="margin: 8px 0;"><strong>${isEn ? 'Check-out' : 'Tarehe ya Kutoka'}:</strong> ${checkOutDate}</p>
            <p style="margin: 8px 0;"><strong>${isEn ? 'Nights' : 'Usiku'}:</strong> ${nights}</p>
            <p style="margin: 8px 0;"><strong>${isEn ? 'Adults' : 'Watu Wazima'}:</strong> ${formData.adults}</p>
            ${parseInt(formData.children) > 0 ? `<p style="margin: 8px 0;"><strong>${isEn ? 'Children' : 'Watoto'}:</strong> ${formData.children}</p>` : ''}
            <p style="margin: 8px 0; font-size: 16px; color: #0d171b;"><strong>${isEn ? 'Total Amount' : 'Jumla'}:</strong> <span style="color: #0d171b; font-weight: bold;">TZS ${total.toLocaleString()}</span></p>
          </div>
      `;
      
      // Add bank accounts section
      if (bankAccounts.length > 0) {
        bookingDetailsHtml += `
          <h3 style="color: #0d171b; margin: 20px 0 15px 0; font-size: 18px; font-weight: bold;">
            ${isEn ? 'Payment Details' : 'Maelezo ya Malipo'}
          </h3>
          <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 15px;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #856404;">
              ${isEn ? 'Please pay through the following bank account details:' : 'Tafadhali lipa kupitia maelezo ya akaunti ya benki yafuatayo:'}
            </p>
        `;
        
        bankAccounts.forEach((account, index) => {
          bookingDetailsHtml += `
            <div style="background: white; padding: 12px; border-radius: 6px; margin-bottom: ${index < bankAccounts.length - 1 ? '10px' : '0'}; border: 1px solid #dee2e6;">
              ${account.bank_name ? `<p style="margin: 5px 0; font-weight: bold; color: #0d171b; font-size: 16px;">${account.bank_name}</p>` : ''}
              <p style="margin: 5px 0; font-weight: bold; color: #0d171b;">${account.name}</p>
              <p style="margin: 5px 0; color: #495057;"><strong>${isEn ? 'Account Number' : 'Nambari ya Akaunti'}:</strong> ${account.account_number}</p>
              ${account.currency ? `<p style="margin: 5px 0; color: #495057;"><strong>${isEn ? 'Currency' : 'Sarafu'}:</strong> ${account.currency}</p>` : ''}
            </div>
          `;
        });
        
        bookingDetailsHtml += `
            <p style="margin: 15px 0 0 0; font-size: 14px; color: #856404;">
              <strong>${isEn ? 'Important' : 'Muhimu'}:</strong> ${isEn 
                ? 'Please confirm payment within 2 hours to secure your booking. After payment, contact us with your booking number and payment proof.' 
                : 'Tafadhali thibitisha malipo ndani ya masaa 2 ili kuhifadhi kuomba kwako. Baada ya malipo, wasiliana nasi kwa nambari yako ya kuomba na uthibitisho wa malipo.'}
            </p>
          </div>
        `;
      } else {
        bookingDetailsHtml += `
          <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
            <p style="margin: 0; color: #856404;">
              <strong>${isEn ? 'Important' : 'Muhimu'}:</strong> ${isEn 
                ? 'Please confirm payment within 2 hours to secure your booking. Contact us for payment details.' 
                : 'Tafadhali thibitisha malipo ndani ya masaa 2 ili kuhifadhi kuomba kwako. Wasiliana nasi kwa maelezo ya malipo.'}
            </p>
          </div>
        `;
      }
      
      bookingDetailsHtml += `</div>`;
      
      // Show SweetAlert with booking details and bank accounts
      await Swal.fire({
        title: isEn ? 'Booking Created Successfully!' : 'Kuomba Kumeundwa Kwa Mafanikio!',
        html: bookingDetailsHtml,
        icon: 'success',
        confirmButtonText: isEn ? 'View My Bookings' : 'Angalia Kuomba Kwangu',
        confirmButtonColor: '#0d171b',
        width: '600px',
        customClass: {
          popup: 'booking-success-popup',
        },
      });
      
      // Redirect to dashboard after closing the alert
      router.push(`/dashboard?success=true&booking=${booking.id}`);
    } catch (err: any) {
      setError(err.message || (isEn ? 'Failed to create booking' : 'Imeshindwa kuunda ombi'));
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isAuthenticated) {
    return null;
  }

  if (isLoading) {
    return (
      <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <Header />
        <main className="flex-1 flex flex-col pt-24 pb-12">
          <div className="max-w-4xl mx-auto px-6 w-full">
            <div className="text-center py-12">
              <span className="material-symbols-outlined text-6xl text-primary animate-spin mb-4">refresh</span>
              <p className="text-gray-600 dark:text-gray-400">
                {isEn ? 'Loading...' : 'Inapakia...'}
              </p>
            </div>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  if (!room) {
    return (
      <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <Header />
        <main className="flex-1 flex flex-col pt-24 pb-12">
          <div className="max-w-4xl mx-auto px-6 w-full">
            <div className="text-center py-12">
              <span className="material-symbols-outlined text-6xl text-gray-400 mb-4">error</span>
              <p className="text-lg font-bold mb-2">
                {isEn ? 'Room not found' : 'Chumba hakijapatikana'}
              </p>
              <Link href="/rooms" className="text-primary hover:underline">
                {isEn ? 'Back to Rooms' : 'Rudi kwenye Vyumba'}
              </Link>
            </div>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  const nights = calculateNights();
  const total = calculateTotal();

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-4xl mx-auto px-6 w-full">
          <div className="mb-6">
            <Link
              href="/rooms"
              className="text-primary font-bold hover:underline flex items-center gap-2 mb-4"
            >
              <span className="material-symbols-outlined">arrow_back</span>
              {isEn ? 'Back to Rooms' : 'Rudi kwenye Vyumba'}
            </Link>
            <h1 className="text-4xl font-black mb-2">
              {isEn ? 'Complete Your Booking' : 'Kamilisha Kuomba Kwako'}
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              {isEn ? `Booking ${room.name}` : `Kuomba ${room.name}`}
            </p>
          </div>

          {error && (
            <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-lg mb-6">
              {error}
            </div>
          )}

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Booking Form */}
            <div className="lg:col-span-2">
              <div className="bg-white dark:bg-background-dark rounded-xl shadow-lg p-6 mb-6">
                <h2 className="text-2xl font-black mb-6">
                  {isEn ? 'Booking Details' : 'Maelezo ya Kuomba'}
                </h2>
                <form onSubmit={handleSubmit} className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-bold mb-2">
                        {isEn ? 'Check-in Date' : 'Tarehe ya Kuingia'}
                      </label>
                      <input
                        type="date"
                        value={formData.checkIn}
                        readOnly
                        disabled
                        className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium opacity-80 cursor-not-allowed"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-bold mb-2">
                        {isEn ? 'Check-out Date' : 'Tarehe ya Kutoka'}
                      </label>
                      <input
                        type="date"
                        value={formData.checkOut}
                        readOnly
                        disabled
                        className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium opacity-80 cursor-not-allowed"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-bold mb-2">
                        {isEn ? 'Adults' : 'Watu Wazima'}
                      </label>
                      <select
                        value={formData.adults}
                        onChange={(e) => setFormData({ ...formData, adults: e.target.value })}
                        className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                        required
                      >
                        {Array.from({ length: room.max_adults }, (_, i) => i + 1).map((num) => (
                          <option key={num} value={num}>
                            {num} {isEn ? (num === 1 ? 'Adult' : 'Adults') : num === 1 ? 'Mtu Mzima' : 'Watu Wazima'}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-bold mb-2">
                        {isEn ? 'Children' : 'Watoto'}
                      </label>
                      <select
                        value={formData.children}
                        onChange={(e) => setFormData({ ...formData, children: e.target.value })}
                        className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                        required
                      >
                        {Array.from({ length: room.max_children + 1 }, (_, i) => i).map((num) => (
                          <option key={num} value={num}>
                            {num} {isEn ? (num === 1 ? 'Child' : 'Children') : num === 1 ? 'Mtoto' : 'Watoto'}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-bold mb-2">
                      {isEn ? 'Special Requests (Optional)' : 'Maombi Maalum (Si Lazima)'}
                    </label>
                    <textarea
                      value={formData.specialRequests}
                      onChange={(e) => setFormData({ ...formData, specialRequests: e.target.value })}
                      className="w-full bg-background-light dark:bg-gray-800 border-none rounded-lg p-4 text-sm focus:ring-2 focus:ring-primary/40 transition-all"
                      rows={4}
                      placeholder={isEn ? 'Any special requests...' : 'Maombi yoyote maalum...'}
                    />
                  </div>

                  <button
                    type="submit"
                    disabled={isSubmitting || nights === 0}
                    className="w-full bg-primary text-white font-bold py-4 rounded-lg hover:bg-primary/90 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  >
                    {isSubmitting ? (
                      <>
                        <span className="material-symbols-outlined animate-spin">refresh</span>
                        <span>{isEn ? 'Processing...' : 'Inachakata...'}</span>
                      </>
                    ) : (
                      <>
                        <span>{isEn ? 'Confirm Booking' : 'Thibitisha Kuomba'}</span>
                        <span className="material-symbols-outlined">check_circle</span>
                      </>
                    )}
                  </button>
                </form>
              </div>
            </div>

            {/* Booking Summary */}
            <div className="lg:col-span-1">
              <div className="bg-white dark:bg-background-dark rounded-xl shadow-lg p-6 sticky top-24">
                <h3 className="text-xl font-black mb-4">
                  {isEn ? 'Booking Summary' : 'Muhtasari wa Kuomba'}
                </h3>
                <div className="space-y-4">
                  <div>
                    <h4 className="font-bold mb-2">{room.name}</h4>
                    {room.room_number && (
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {isEn ? `Room ${room.room_number}` : `Chumba ${room.room_number}`}
                      </p>
                    )}
                  </div>
                  <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600 dark:text-gray-400">
                        {isEn ? 'Price per night' : 'Bei kwa usiku'}
                      </span>
                      <span className="font-bold">TZS {room.price.toLocaleString()}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600 dark:text-gray-400">
                        {isEn ? 'Nights' : 'Usiku'}
                      </span>
                      <span className="font-bold">{nights}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600 dark:text-gray-400">
                        {isEn ? 'Adults' : 'Watu Wazima'}
                      </span>
                      <span className="font-bold">{formData.adults}</span>
                    </div>
                    {parseInt(formData.children) > 0 && (
                      <div className="flex justify-between text-sm">
                        <span className="text-gray-600 dark:text-gray-400">
                          {isEn ? 'Children' : 'Watoto'}
                        </span>
                        <span className="font-bold">{formData.children}</span>
                      </div>
                    )}
                  </div>
                  <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div className="flex justify-between items-center">
                      <span className="text-lg font-black">
                        {isEn ? 'Total' : 'Jumla'}
                      </span>
                      <span className="text-2xl font-black text-primary">
                        TZS {total.toLocaleString()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}
