'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { bookingsAPI, Booking } from '@/lib/api/bookings';
import { messagesAPI } from '@/lib/api/messages';
import BookingDateModal from '@/components/BookingDateModal';
import Swal from 'sweetalert2';

export default function DashboardPage() {
  const { user, isAuthenticated, isLoading: authLoading } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';
  const router = useRouter();
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [upcomingBooking, setUpcomingBooking] = useState<Booking | null>(null);
  const [isBookingModalOpen, setIsBookingModalOpen] = useState(false);
  const [messages, setMessages] = useState<any[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);

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
      try {
        const fetchedBookings = await bookingsAPI.getMyBookings();
        setBookings(fetchedBookings);
        
        // Find upcoming booking (confirmed or online_booking with future check-in)
        const upcoming = fetchedBookings.find(
          (b) =>
            (b.status === 'confirmed' || b.status === 'online_booking') &&
            new Date(b.check_in) >= new Date()
        );
        setUpcomingBooking(upcoming || null);
      } catch (err) {
        console.error('Error fetching bookings:', err);
      } finally {
        setIsLoading(false);
      }
    };

    const fetchMessages = async () => {
      try {
        const fetchedMessages = await messagesAPI.getMyMessages();
        if (Array.isArray(fetchedMessages)) {
          setMessages(fetchedMessages);
          const unread = fetchedMessages.filter((m: any) => !m.response).length;
          setUnreadCount(unread);
        } else {
          setMessages([]);
          setUnreadCount(0);
        }
      } catch (err) {
        console.error('Error fetching messages:', err);
        setMessages([]);
        setUnreadCount(0);
      }
    };

    fetchBookings();
    if (isAuthenticated) {
      fetchMessages();
    }
  }, [isAuthenticated, authLoading, router]);

  if (authLoading) {
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

  const totalBookings = bookings.length;
  const confirmedBookings = bookings.filter((b) => b.status === 'confirmed').length;
  const totalSpent = bookings.reduce((sum, b) => sum + ((b.total_price || b.total_amount || 0) as number), 0);
  const totalPaid = bookings.reduce((sum, b) => sum + ((b.paid_amount || 0) as number), 0);
  const loyaltyPoints = totalBookings * 1000; // Mock calculation
  const memberStatus = totalBookings >= 10 ? 'Platinum' : totalBookings >= 5 ? 'Gold' : 'Silver';

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-7xl mx-auto px-6 w-full">
          {/* Welcome Header */}
          <div className="mb-8 flex flex-wrap justify-between items-start gap-4">
            <div>
              <h2 className="text-[#0e141b] dark:text-white text-3xl lg:text-4xl font-black leading-tight tracking-[-0.033em] mb-2">
                {isEn ? `Welcome back, ${user?.first_name || user?.name || 'Guest'}!` : `Karibu tena, ${user?.first_name || user?.name || 'Mgeni'}!`}
              </h2>
              <p className="text-[#4e7397] dark:text-slate-400 text-base font-normal">
                {isEn ? 'Manage your upcoming stays and loyalty rewards.' : 'Simamia makao yako yanayokuja na malipo ya uaminifu.'}
              </p>
            </div>
            <button
              onClick={() => setIsBookingModalOpen(true)}
              className="bg-primary text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow-md hover:bg-primary/90 transition-colors flex items-center gap-2"
            >
              <span className="material-symbols-outlined text-base">add</span>
              {isEn ? 'New Booking' : 'Omba Mpya'}
            </button>
          </div>

          {/* Hero Section: Upcoming Stay */}
          {upcomingBooking && (
            <section className="mb-8">
              <div className="bg-white dark:bg-slate-900 rounded-xl overflow-hidden shadow-sm border border-[#d0dbe7] dark:border-slate-800">
                <div className="flex flex-col lg:flex-row">
                  {/* Image Column */}
                  <div className="w-full lg:w-2/5 h-64 lg:h-auto bg-slate-200 dark:bg-slate-800 relative overflow-hidden">
                    {upcomingBooking.room?.images?.[0] ? (
                      <img
                        src={upcomingBooking.room.images[0]}
                        alt={upcomingBooking.room?.name || 'Room'}
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          (e.target as HTMLImageElement).src = 'https://via.placeholder.com/800x600?text=Room';
                        }}
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-800">
                        <span className="material-symbols-outlined text-6xl text-slate-400">hotel</span>
                      </div>
                    )}
                  </div>
                  {/* Content Column */}
                  <div className="flex-1 p-6 lg:p-10 flex flex-col justify-between gap-6">
                    <div>
                      <div className="flex justify-between items-start mb-2">
                        <span
                          className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide ${
                            upcomingBooking.status === 'confirmed'
                              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                              : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                          }`}
                        >
                          {upcomingBooking.status === 'confirmed'
                            ? isEn
                              ? 'Confirmed'
                              : 'Imeidhinishwa'
                            : isEn
                            ? 'Online Booking'
                            : 'Ombi la Mtandaoni'}
                        </span>
                        <p className="text-[#4e7397] dark:text-slate-400 text-sm font-medium">
                          #{upcomingBooking.id}
                        </p>
                      </div>
                      <h3 className="text-[#0e141b] dark:text-white text-2xl lg:text-3xl font-bold leading-tight mb-2">
                        {upcomingBooking.room?.name || `Room ${upcomingBooking.room_id}`}
                      </h3>
                      <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-2 text-[#4e7397] dark:text-slate-400">
                          <span className="material-symbols-outlined text-sm">calendar_today</span>
                          <p className="text-base font-medium">
                            {formatDate(upcomingBooking.check_in)} - {formatDate(upcomingBooking.check_out)}
                          </p>
                        </div>
                        <div className="flex items-center gap-2 text-[#4e7397] dark:text-slate-400">
                          <span className="material-symbols-outlined text-sm">bed</span>
                          <p className="text-base font-normal">
                            {upcomingBooking.adults} {isEn ? (upcomingBooking.adults === 1 ? 'Adult' : 'Adults') : upcomingBooking.adults === 1 ? 'Mtu Mzima' : 'Watu Wazima'}
                            {upcomingBooking.children > 0 &&
                              `, ${upcomingBooking.children} ${isEn ? (upcomingBooking.children === 1 ? 'Child' : 'Children') : upcomingBooking.children === 1 ? 'Mtoto' : 'Watoto'}`}
                          </p>
                        </div>
                      </div>
                    </div>
                    {/* Action Bar Integrated */}
                    <div className="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                      <Link
                        href={`/my-bookings/${upcomingBooking.hashid || upcomingBooking.id}`}
                        className="flex-1 min-w-[140px] flex items-center justify-center gap-2 rounded-xl bg-primary py-3 px-6 text-white text-sm font-bold transition-transform active:scale-95"
                      >
                        <span className="material-symbols-outlined text-[20px]">visibility</span>
                        <span>{isEn ? 'View Booking' : 'Angalia Ombi'}</span>
                      </Link>
                      <div className="flex flex-wrap gap-2">
                        <Link
                          href={`/my-bookings/${upcomingBooking.hashid || upcomingBooking.id}`}
                          className="size-11 flex items-center justify-center rounded-xl bg-[#e7edf3] dark:bg-slate-800 text-[#0e141b] dark:text-white hover:bg-primary/10 hover:text-primary transition-all group"
                          title={isEn ? 'Modify Dates' : 'Hariri Tarehe'}
                        >
                          <span className="material-symbols-outlined text-[22px]">calendar_add_on</span>
                        </Link>
                        <Link
                          href={`/my-bookings/${upcomingBooking.hashid || upcomingBooking.id}`}
                          className="size-11 flex items-center justify-center rounded-xl bg-[#e7edf3] dark:bg-slate-800 text-[#0e141b] dark:text-white hover:bg-primary/10 hover:text-primary transition-all"
                          title={isEn ? 'Add Services' : 'Ongeza Huduma'}
                        >
                          <span className="material-symbols-outlined text-[22px]">restaurant</span>
                        </Link>
                        <button
                          onClick={async () => {
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
                                await bookingsAPI.cancelBooking(upcomingBooking.hashid || upcomingBooking.id);
                                const fetchedBookings = await bookingsAPI.getMyBookings();
                                setBookings(fetchedBookings);
                                setUpcomingBooking(null);
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
                          }}
                          className="size-11 flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 transition-all"
                          title={isEn ? 'Cancel Booking' : 'Ghairi Ombi'}
                        >
                          <span className="material-symbols-outlined text-[22px]">close</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          )}

          {/* Stats & Summary */}
          <section className="pb-12">
            <div className="flex flex-col gap-6">
              <h4 className="text-lg font-bold">{isEn ? 'Your Rewards Overview' : 'Muhtasari wa Malipo Yako'}</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {/* Total Amount Paid Card */}
                <div className="flex flex-col gap-4 rounded-xl p-6 bg-white dark:bg-slate-900 border border-[#d0dbe7] dark:border-slate-800 shadow-sm">
                  <div className="flex justify-between items-start">
                    <div className="rounded-full bg-primary/10 p-3 text-primary">
                      <span className="material-symbols-outlined text-[28px]">payments</span>
                    </div>
                    <span className="text-[#078838] bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded text-xs font-bold">
                      {isEn ? 'All Time' : 'Wakati Wote'}
                    </span>
                  </div>
                  <div>
                    <p className="text-[#4e7397] dark:text-slate-400 text-sm font-medium leading-normal uppercase tracking-wider">
                      {isEn ? 'Total Amount Paid So Far' : 'Jumla ya Kiasi Kilicholipwa Hadi Sasa'}
                    </p>
                    <p className="text-[#0e141b] dark:text-white tracking-tight text-3xl font-black leading-tight">
                      TZS {totalPaid.toLocaleString()}
                    </p>
                  </div>
                  <div className="flex items-center gap-1 text-[#078838] text-sm font-medium">
                    <span className="material-symbols-outlined text-sm">trending_up</span>
                    <span>{isEn ? 'Across all bookings' : 'Kwenye maombi yote'}</span>
                  </div>
                </div>

                {/* Total Bookings Card */}
                <div className="flex flex-col gap-4 rounded-xl p-6 bg-white dark:bg-slate-900 border border-[#d0dbe7] dark:border-slate-800 shadow-sm">
                  <div className="flex justify-between items-start">
                    <div className="rounded-full bg-primary/10 p-3 text-primary">
                      <span className="material-symbols-outlined text-[28px]">hotel</span>
                    </div>
                    <span className="text-primary bg-primary/10 px-2 py-1 rounded text-xs font-bold">
                      {memberStatus} {isEn ? 'Member' : 'Mwanachama'}
                    </span>
                  </div>
                  <div>
                    <p className="text-[#4e7397] dark:text-slate-400 text-sm font-medium leading-normal uppercase tracking-wider">
                      {isEn ? 'Total Stays' : 'Jumla ya Makao'}
                    </p>
                    <p className="text-[#0e141b] dark:text-white tracking-tight text-3xl font-black leading-tight">
                      {totalBookings} {isEn ? 'Bookings' : 'Maombi'}
                    </p>
                  </div>
                  <div className="flex items-center gap-1 text-[#078838] text-sm font-medium">
                    <span className="material-symbols-outlined text-sm">trending_up</span>
                    <span>+{confirmedBookings} {isEn ? 'stay since last year' : 'makao tangu mwaka jana'}</span>
                  </div>
                </div>

                {/* Quick Support Mini Card */}
                <div className="flex flex-col gap-4 rounded-xl p-6 bg-primary text-white shadow-lg lg:col-span-1 md:col-span-2">
                  <div className="flex justify-between items-start">
                    <div className="rounded-full bg-white/20 p-3">
                      <span className="material-symbols-outlined text-[28px]">support_agent</span>
                    </div>
                  </div>
                  <div>
                    <p className="text-white/80 text-sm font-medium leading-normal uppercase tracking-wider">
                      {isEn ? 'Concierge 24/7' : 'Msaada 24/7'}
                    </p>
                    <p className="text-white tracking-tight text-xl font-bold leading-tight">
                      {isEn ? 'Need Assistance?' : 'Unahitaji Msaada?'}
                    </p>
                  </div>
                  <Link
                    href="/contact"
                    className="mt-2 bg-white text-primary py-2 px-4 rounded-lg text-sm font-bold hover:bg-slate-100 transition-colors inline-block"
                  >
                    {isEn ? 'Chat with Us' : 'Wasiliana Nasi'}
                  </Link>
                </div>
              </div>
            </div>
          </section>

          {/* Messages & Notifications */}
          <section className="pb-12">
            <div className="flex flex-col gap-6">
              <h4 className="text-lg font-bold">{isEn ? 'Messages & Notifications' : 'Ujumbe na Arifa'}</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Messages Card */}
                <div className="flex flex-col gap-4 rounded-xl p-6 bg-white dark:bg-slate-900 border border-[#d0dbe7] dark:border-slate-800 shadow-sm">
                  <div className="flex justify-between items-start">
                    <div className="rounded-full bg-primary/10 p-3 text-primary">
                      <span className="material-symbols-outlined text-[28px]">mail</span>
                    </div>
                    {unreadCount > 0 && (
                      <span className="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                        {unreadCount} {isEn ? 'New' : 'Mpya'}
                      </span>
                    )}
                  </div>
                  <div>
                    <p className="text-[#4e7397] dark:text-slate-400 text-sm font-medium leading-normal uppercase tracking-wider">
                      {isEn ? 'Your Messages' : 'Ujumbe Wako'}
                    </p>
                    <p className="text-[#0e141b] dark:text-white tracking-tight text-2xl font-black leading-tight">
                      {messages.length} {isEn ? (messages.length === 1 ? 'Message' : 'Messages') : messages.length === 1 ? 'Ujumbe' : 'Ujumbe'}
                    </p>
                  </div>
                  {messages.length > 0 ? (
                    <div className="space-y-2 max-h-32 overflow-y-auto">
                      {messages.slice(0, 3).map((msg: any) => (
                        <div key={msg.id} className="flex items-start gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                          <span className={`material-symbols-outlined text-sm ${msg.response ? 'text-green-500' : 'text-amber-500'}`}>
                            {msg.response ? 'check_circle' : 'schedule'}
                          </span>
                          <div className="flex-1 min-w-0">
                            <p className="text-xs font-bold truncate">{msg.subject}</p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                              {new Date(msg.created_at).toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
                                month: 'short',
                                day: 'numeric',
                              })}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      {isEn ? 'No messages yet' : 'Hakuna ujumbe bado'}
                    </p>
                  )}
                  <Link
                    href="/contact"
                    className="text-primary text-sm font-bold hover:underline flex items-center gap-1"
                  >
                    <span>{isEn ? 'Contact Us' : 'Wasiliana Nasi'}</span>
                    <span className="material-symbols-outlined text-sm">arrow_forward</span>
                  </Link>
                </div>

                {/* Notifications Card */}
                <div className="flex flex-col gap-4 rounded-xl p-6 bg-white dark:bg-slate-900 border border-[#d0dbe7] dark:border-slate-800 shadow-sm">
                  <div className="flex justify-between items-start">
                    <div className="rounded-full bg-primary/10 p-3 text-primary">
                      <span className="material-symbols-outlined text-[28px]">notifications</span>
                    </div>
                  </div>
                  <div>
                    <p className="text-[#4e7397] dark:text-slate-400 text-sm font-medium leading-normal uppercase tracking-wider">
                      {isEn ? 'Admin Responses' : 'Majibu ya Msimamizi'}
                    </p>
                    <p className="text-[#0e141b] dark:text-white tracking-tight text-2xl font-black leading-tight">
                      {messages.filter((m: any) => m.response).length} {isEn ? 'Responses' : 'Majibu'}
                    </p>
                  </div>
                  {messages.filter((m: any) => m.response).length > 0 ? (
                    <div className="space-y-2 max-h-32 overflow-y-auto">
                      {messages.filter((m: any) => m.response).slice(0, 3).map((msg: any) => (
                        <div key={msg.id} className="flex items-start gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                          <span className="material-symbols-outlined text-sm text-green-500">check_circle</span>
                          <div className="flex-1 min-w-0">
                            <p className="text-xs font-bold truncate">{msg.subject}</p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                              {msg.responded_at
                                ? new Date(msg.responded_at).toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
                                    month: 'short',
                                    day: 'numeric',
                                  })
                                : ''}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      {isEn ? 'No responses yet' : 'Hakuna majibu bado'}
                    </p>
                  )}
                  <Link
                    href="/contact"
                    className="text-primary text-sm font-bold hover:underline flex items-center gap-1"
                  >
                    <span>{isEn ? 'Contact Us' : 'Wasiliana Nasi'}</span>
                    <span className="material-symbols-outlined text-sm">arrow_forward</span>
                  </Link>
                </div>
              </div>
            </div>
          </section>
        </div>
      </main>
      <Footer />
      <BookingDateModal isOpen={isBookingModalOpen} onClose={() => setIsBookingModalOpen(false)} />
    </div>
  );
}
