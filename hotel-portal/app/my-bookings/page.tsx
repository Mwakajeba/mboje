'use client';

import React, { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { bookingsAPI, Booking } from '@/lib/api/bookings';
import Link from 'next/link';
import Swal from 'sweetalert2';

export default function MyBookingsPage() {
  const { user, isAuthenticated, isLoading: authLoading } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';
  const router = useRouter();
  const searchParams = useSearchParams();
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<'upcoming' | 'past' | 'cancelled'>('upcoming');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    // Wait for auth to finish loading before checking
    if (authLoading) return;
    
    if (!isAuthenticated) {
      // Save current page before redirecting
      if (typeof window !== 'undefined') {
        sessionStorage.setItem('redirect_url', window.location.pathname + window.location.search);
      }
      router.push('/login');
      return;
    }

    const fetchBookings = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const fetchedBookings = await bookingsAPI.getMyBookings();
        setBookings(fetchedBookings);
      } catch (err: any) {
        console.error('Error fetching bookings:', err);
        setError(err.message || (isEn ? 'Failed to load bookings' : 'Imeshindwa kupakia maombi'));
      } finally {
        setIsLoading(false);
      }
    };

    fetchBookings();
  }, [isAuthenticated, authLoading, router, isEn]);

  useEffect(() => {
    if (searchParams?.get('success') === 'true') {
      const message = isEn
        ? 'Booking created successfully! Please confirm payment within 2 hours.'
        : 'Ombi limeundwa kwa mafanikio! Tafadhali thibitisha malipo ndani ya masaa 2.';
      alert(message);
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.delete('success');
      newUrl.searchParams.delete('booking');
      window.history.replaceState({}, '', newUrl.toString());
    }
  }, [searchParams, isEn]);

  const handleCancel = async (bookingId: number | string) => {
    const result = await Swal.fire({
      title: isEn ? 'Cancel Booking?' : 'Ghairi Ombi?',
      text: isEn ? 'Are you sure you want to cancel this booking? This action cannot be undone.' : 'Una uhakika unataka kughairi ombi hili? Kitendo hiki hakiwezi kufutwa.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: isEn ? 'Yes, Cancel Booking' : 'Ndio, Ghairi Ombi',
      cancelButtonText: isEn ? 'No, Keep Booking' : 'Hapana, Weka Ombi',
      reverseButtons: true,
    });

    if (result.isConfirmed) {
    try {
      await bookingsAPI.cancelBooking(bookingId);
      const fetchedBookings = await bookingsAPI.getMyBookings();
      setBookings(fetchedBookings);
        await Swal.fire({
          title: isEn ? 'Cancelled!' : 'Imeghairiwa!',
          text: isEn ? 'Your booking has been cancelled successfully.' : 'Ombi lako limeghairiwa kwa mafanikio.',
          icon: 'success',
          confirmButtonColor: '#2563eb',
        });
    } catch (err: any) {
        await Swal.fire({
          title: isEn ? 'Error!' : 'Kosa!',
          text: err.message || (isEn ? 'Failed to cancel booking' : 'Imeshindwa kughairi ombi'),
          icon: 'error',
          confirmButtonColor: '#dc2626',
        });
      }
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <span className="material-symbols-outlined text-6xl text-primary animate-spin mb-4">refresh</span>
          <p className="text-gray-600 dark:text-gray-400">{isEn ? 'Loading...' : 'Inapakia...'}</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  const getStatusBadge = (status: string) => {
    const statusMap: { [key: string]: { text: string; bg: string; textColor: string } } = {
      confirmed: {
        text: isEn ? 'Confirmed' : 'Imeidhinishwa',
        bg: 'bg-green-500',
        textColor: 'text-white',
      },
      checked_in: {
        text: isEn ? 'Checked In' : 'Ameingia',
        bg: 'bg-blue-500',
        textColor: 'text-white',
      },
      checked_out: {
        text: isEn ? 'Checked Out' : 'Ameondoka',
        bg: 'bg-gray-500',
        textColor: 'text-white',
      },
      online_booking: {
        text: isEn ? 'Online Booking' : 'Ombi la Mtandaoni',
        bg: 'bg-purple-500',
        textColor: 'text-white',
      },
      pending: {
        text: isEn ? 'Hold' : 'Subiri',
        bg: 'bg-amber-500',
        textColor: 'text-white',
      },
      cancelled: {
        text: isEn ? 'Cancelled' : 'Imeghairiwa',
        bg: 'bg-red-500',
        textColor: 'text-white',
      },
    };

    const statusInfo = statusMap[status] || {
      text: status,
      bg: 'bg-gray-500',
      textColor: 'text-white',
    };

    return (
      <div className={`absolute top-2 left-2 ${statusInfo.bg} ${statusInfo.textColor} text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider shadow-sm`}>
        {statusInfo.text}
      </div>
    );
  };

  const getPaymentStatus = (booking: Booking) => {
    // Use actual payment_status from API
    const paymentStatus = booking.payment_status || 'pending';
    
    const statusMap: { [key: string]: { status: string; color: string; text: string } } = {
      paid: {
        status: 'paid',
        color: 'bg-green-500',
        text: isEn ? 'Paid' : 'Imelipwa',
      },
      partial: {
        status: 'partial',
        color: 'bg-blue-500',
        text: isEn ? 'Partial' : 'Sehemu',
      },
      refunded: {
        status: 'refunded',
        color: 'bg-purple-500',
        text: isEn ? 'Refunded' : 'Imerudishwa',
      },
      pending: {
        status: 'pending',
        color: 'bg-amber-500',
        text: isEn ? 'Pending' : 'Inasubiri',
      },
    };

    return statusMap[paymentStatus] || statusMap.pending;
  };

  const filteredBookings = bookings.filter((booking) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkOutDate = new Date(booking.check_out);
    checkOutDate.setHours(0, 0, 0, 0);
    const checkInDate = new Date(booking.check_in);
    checkInDate.setHours(0, 0, 0, 0);

    if (activeTab === 'upcoming') {
      // Show: confirmed/online_booking/pending with future check-out, OR checked_in with future check-out
      return (
        (booking.status === 'confirmed' || 
         booking.status === 'online_booking' || 
         booking.status === 'pending' ||
         booking.status === 'checked_in') &&
        checkOutDate >= today
      );
    }
    if (activeTab === 'past') {
      // Show: checked_out bookings, OR checked_in/confirmed with past check-out date
      return (
        booking.status === 'checked_out' ||
        (booking.status === 'checked_in' && checkOutDate < today) ||
        (booking.status === 'confirmed' && checkOutDate < today)
      );
    }
    if (activeTab === 'cancelled') {
      return booking.status === 'cancelled';
    }
    return true;
  });

  const upcomingBookings = bookings.filter((b) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkOut = new Date(b.check_out);
    checkOut.setHours(0, 0, 0, 0);
    return (b.status === 'confirmed' || b.status === 'online_booking' || b.status === 'checked_in') && 
           checkOut >= today;
  });

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-7xl mx-auto px-6 w-full">
          {/* Breadcrumbs */}
          <div className="flex flex-wrap gap-2 items-center mb-6">
            <Link href="/dashboard" className="text-[#4e7397] text-sm font-medium hover:text-primary">
              {isEn ? 'Home' : 'Nyumbani'}
            </Link>
            <span className="text-[#4e7397] text-sm font-medium">/</span>
            <span className="text-[#0e141b] dark:text-white text-sm font-medium">
              {isEn ? 'My Bookings' : 'Maombi Yangu'}
            </span>
          </div>

          {/* Page Heading */}
          <div className="flex flex-wrap justify-between items-end gap-3 mb-6">
            <div className="flex min-w-72 flex-col gap-1">
              <h1 className="text-[#0e141b] dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
                {isEn ? 'My Bookings' : 'Maombi Yangu'}
              </h1>
              <p className="text-[#4e7397] text-base font-normal leading-normal">
                {isEn
                  ? 'Manage your upcoming, past, and cancelled reservations.'
                  : 'Simamia maombi yako yanayokuja, yaliyopita, na yaliyoghairiwa.'}
              </p>
            </div>
            <Link
              href="/#room-search"
              className="bg-primary text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow-md hover:bg-primary/90 transition-colors"
            >
              + {isEn ? 'New Booking' : 'Omba Mpya'}
            </Link>
          </div>

          {/* Tabs */}
          <div className="border-b border-[#d0dbe7] dark:border-slate-800 mb-6">
            <div className="flex gap-8">
              <button
                onClick={() => setActiveTab('upcoming')}
                className={`flex flex-col items-center justify-center border-b-[3px] pb-[13px] pt-4 ${
                  activeTab === 'upcoming'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-[#4e7397] hover:text-[#0e141b] dark:hover:text-white'
                }`}
              >
                <p className="text-sm font-bold leading-normal tracking-[0.015em]">
                  {isEn ? 'Upcoming' : 'Yanayokuja'} ({upcomingBookings.length})
                </p>
              </button>
              <button
                onClick={() => setActiveTab('past')}
                className={`flex flex-col items-center justify-center border-b-[3px] pb-[13px] pt-4 ${
                  activeTab === 'past'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-[#4e7397] hover:text-[#0e141b] dark:hover:text-white'
                }`}
              >
                <p className="text-sm font-bold leading-normal tracking-[0.015em]">{isEn ? 'Past' : 'Yaliyopita'}</p>
              </button>
              <button
                onClick={() => setActiveTab('cancelled')}
                className={`flex flex-col items-center justify-center border-b-[3px] pb-[13px] pt-4 ${
                  activeTab === 'cancelled'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-[#4e7397] hover:text-[#0e141b] dark:hover:text-white'
                }`}
              >
                <p className="text-sm font-bold leading-normal tracking-[0.015em]">
                  {isEn ? 'Cancelled' : 'Yaliyoghairiwa'}
                </p>
              </button>
            </div>
          </div>

          {/* Bookings List */}
          {isLoading ? (
            <div className="text-center py-12">
              <span className="material-symbols-outlined text-6xl text-primary animate-spin mb-4">refresh</span>
              <p className="text-gray-600 dark:text-gray-400">{isEn ? 'Loading bookings...' : 'Inapakia maombi...'}</p>
            </div>
          ) : error ? (
            <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-lg">
              {error}
            </div>
          ) : filteredBookings.length === 0 ? (
            <div className="bg-white dark:bg-slate-900 rounded-xl p-6 border border-[#e7edf3] dark:border-slate-800">
              <div className="text-center py-12">
                <span className="material-symbols-outlined text-6xl text-gray-400 mb-4">event_busy</span>
                <p className="text-xl font-bold mb-2">
                  {isEn ? 'No bookings found' : 'Hakuna maombi yaliyopatikana'}
                </p>
                {activeTab !== 'past' && (
                  <>
                <p className="text-gray-600 dark:text-gray-400 mb-6">
                  {isEn ? 'Start by booking a new room' : 'Anza kwa kuomba chumba kipya'}
                </p>
                <Link
                  href="/rooms"
                  className="inline-flex items-center gap-2 bg-primary text-white font-bold px-6 py-3 rounded-lg hover:bg-primary/90 transition-all"
                >
                  <span className="material-symbols-outlined">hotel</span>
                  <span>{isEn ? 'Book Room' : 'Omba Chumba'}</span>
                </Link>
                  </>
                )}
              </div>
            </div>
          ) : (
            <div className="flex flex-col gap-4">
              {filteredBookings.map((booking) => {
                const paymentStatus = getPaymentStatus(booking);
                return (
                  <div
                    key={booking.id}
                    className="bg-white dark:bg-slate-900 border border-[#e7edf3] dark:border-slate-800 rounded-xl p-5 flex gap-6 hover:shadow-lg transition-shadow"
                  >
                    <div className="w-48 h-32 flex-shrink-0 bg-slate-200 dark:bg-slate-800 rounded-lg overflow-hidden relative">
                      {getStatusBadge(booking.status)}
                      {booking.room?.images?.[0] ? (
                      <img
                        alt={booking.room?.name || 'Room'}
                        className="w-full h-full object-cover"
                          src={booking.room.images[0]}
                          onError={(e) => {
                            (e.target as HTMLImageElement).src = 'https://via.placeholder.com/400x300?text=Room';
                          }}
                      />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-800">
                          <span className="material-symbols-outlined text-4xl text-slate-400">hotel</span>
                        </div>
                      )}
                    </div>
                    <div className="flex-1 flex flex-col justify-between">
                      <div className="flex justify-between items-start">
                        <div>
                          <h3 className="text-lg font-bold text-[#0e141b] dark:text-white">
                            {booking.room?.name || `Room ${booking.room_id}`}
                          </h3>
                          <p className="text-sm text-[#4e7397] flex items-center gap-1">
                            <span className="material-symbols-outlined text-xs">location_on</span>
                            {booking.branch?.name || (isEn ? 'Hotel Location' : 'Eneo la Hoteli')}
                            {booking.branch?.address && (
                              <span className="ml-1">• {booking.branch.address}</span>
                            )}
                          </p>
                        </div>
                        <div className="text-right">
                          <p className="text-xs text-[#4e7397] font-medium uppercase tracking-wider">
                            {isEn ? 'Booking ID' : 'Kitambulisho'}
                          </p>
                          <p className="text-sm font-bold">#{booking.id}</p>
                        </div>
                      </div>
                      <div className={`grid gap-4 mt-2 ${activeTab === 'past' ? 'grid-cols-5' : 'grid-cols-4'}`}>
                        <div className="flex flex-col">
                          <span className="text-[10px] uppercase text-[#4e7397] font-bold tracking-widest">
                            {isEn ? 'Dates' : 'Tarehe'}
                          </span>
                          <span className="text-sm font-medium">
                            {formatDate(booking.check_in)} - {formatDate(booking.check_out)}
                          </span>
                        </div>
                        <div className="flex flex-col">
                          <span className="text-[10px] uppercase text-[#4e7397] font-bold tracking-widest">
                            {isEn ? 'Guests' : 'Wageni'}
                          </span>
                          <span className="text-sm font-medium">
                            {booking.adults} {isEn ? (booking.adults === 1 ? 'Adult' : 'Adults') : booking.adults === 1 ? 'Mtu Mzima' : 'Watu Wazima'}
                            {booking.children > 0 &&
                              `, ${booking.children} ${isEn ? (booking.children === 1 ? 'Child' : 'Children') : booking.children === 1 ? 'Mtoto' : 'Watoto'}`}
                          </span>
                        </div>
                        {activeTab === 'past' && (
                          <div className="flex flex-col">
                            <span className="text-[10px] uppercase text-[#4e7397] font-bold tracking-widest">
                              {isEn ? 'Status' : 'Hali'}
                            </span>
                            <div className="flex items-center gap-1.5 mt-0.5">
                              {booking.status === 'checked_out' ? (
                                <>
                                  <span className="material-symbols-outlined text-xs text-green-600">check_circle</span>
                                  <span className="text-sm font-medium text-green-600 dark:text-green-400">
                                    {isEn ? 'Checked Out' : 'Ameondoka'}
                                  </span>
                                </>
                              ) : booking.status === 'checked_in' ? (
                                <>
                                  <span className="material-symbols-outlined text-xs text-blue-600">hotel</span>
                                  <span className="text-sm font-medium text-blue-600 dark:text-blue-400">
                                    {isEn ? 'Checked In' : 'Ameingia'}
                                  </span>
                                </>
                              ) : (
                                <>
                                  <span className="material-symbols-outlined text-xs text-amber-600">schedule</span>
                                  <span className="text-sm font-medium text-amber-600 dark:text-amber-400">
                                    {isEn ? 'Confirmed' : 'Imeidhinishwa'}
                                  </span>
                                </>
                              )}
                            </div>
                          </div>
                        )}
                        <div className="flex flex-col">
                          <span className="text-[10px] uppercase text-[#4e7397] font-bold tracking-widest">
                            {isEn ? 'Amount' : 'Kiasi'}
                          </span>
                          <span className="text-sm font-bold text-primary">
                            TZS {((booking.total_price || booking.total_amount || 0) as number).toLocaleString()}
                          </span>
                        </div>
                        <div className="flex flex-col">
                          <span className="text-[10px] uppercase text-[#4e7397] font-bold tracking-widest">
                            {isEn ? 'Payment' : 'Malipo'}
                          </span>
                          <div className="flex items-center gap-1.5 mt-0.5">
                            <span className={`size-2 rounded-full ${paymentStatus.color}`}></span>
                            <span
                              className={`text-sm font-medium ${
                                paymentStatus.status === 'paid'
                                  ? 'text-green-600 dark:text-green-400'
                                  : 'text-amber-600 dark:text-amber-400'
                              }`}
                            >
                              {paymentStatus.text}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="flex flex-col justify-center gap-2 border-l border-[#e7edf3] dark:border-slate-800 pl-6 w-48">
                      <Link
                        href={`/my-bookings/${booking.hashid || booking.id}`}
                        className="w-full bg-primary text-white text-sm py-2 rounded-lg font-bold hover:bg-primary/90 transition-colors text-center"
                      >
                        {isEn ? 'View Details' : 'Angalia Maelezo'}
                      </Link>
                      {activeTab === 'past' && booking.status === 'checked_out' && (
                        <button
                          onClick={async () => {
                            if (!booking.hashid) return;
                            try {
                              await bookingsAPI.downloadReceipt(booking.hashid);
                            } catch (err: any) {
                              await Swal.fire({
                                title: isEn ? 'Error!' : 'Kosa!',
                                text: err.message || (isEn ? 'Failed to download receipt' : 'Imeshindwa kupakua risiti'),
                                icon: 'error',
                                confirmButtonColor: '#dc2626',
                              });
                            }
                          }}
                          className="w-full border border-slate-200 dark:border-slate-700 text-sm py-2 rounded-lg font-bold hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-center gap-2"
                        >
                          <span className="material-symbols-outlined text-sm">download</span>
                          {isEn ? 'Download Receipt' : 'Pakua Risiti'}
                        </button>
                      )}
                      {activeTab !== 'past' && (
                        <Link
                          href={`/my-bookings/${booking.hashid || booking.id}`}
                          className="w-full border border-slate-200 dark:border-slate-700 text-sm py-2 rounded-lg font-bold hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-center gap-2"
                        >
                        <span className="material-symbols-outlined text-sm">download</span>
                        {isEn ? 'Invoice' : 'Risiti'}
                        </Link>
                      )}
                      {booking.status === 'pending' || booking.status === 'online_booking' ? (
                        <button
                          onClick={() => handleCancel(booking.hashid || booking.id)}
                          className="text-xs text-red-500 font-medium hover:underline mt-1"
                        >
                          {isEn ? 'Modify or Cancel' : 'Hariri au Ghairi'}
                        </button>
                      ) : null}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </main>
      <Footer />
    </div>
  );
}
