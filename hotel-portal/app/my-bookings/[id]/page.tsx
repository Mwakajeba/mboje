'use client';

import React, { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import Link from 'next/link';
import Swal from 'sweetalert2';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { bookingsAPI, Booking } from '@/lib/api/bookings';
import { settingsAPI, CompanySettings } from '@/lib/api/settings';
import * as QRCode from 'qrcode';

export default function BookingDetailsPage() {
  const { isAuthenticated, isLoading: authLoading } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';
  const router = useRouter();
  const params = useParams();
  const bookingId = params?.id as string;
  const [booking, setBooking] = useState<Booking | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [companySettings, setCompanySettings] = useState<CompanySettings | null>(null);
  const [isDownloading, setIsDownloading] = useState(false);
  const [qrCodeDataUrl, setQrCodeDataUrl] = useState<string | null>(null);

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

    const fetchBooking = async () => {
      if (!bookingId) return;
      setIsLoading(true);
      setError(null);
      try {
        const [fetchedBooking, companyData] = await Promise.all([
          bookingsAPI.getBookingById(bookingId),
          settingsAPI.getCompanySettings().catch(() => null),
        ]);
        setBooking(fetchedBooking);
        setCompanySettings(companyData);
        
        // Generate QR code for check-in
        if (fetchedBooking && (fetchedBooking.hashid || fetchedBooking.id)) {
          const qrData = JSON.stringify({
            type: 'booking_checkin',
            booking_id: fetchedBooking.hashid || fetchedBooking.id,
            booking_number: fetchedBooking.booking_number,
            guest_id: fetchedBooking.guest_id,
            check_in: fetchedBooking.check_in,
            check_out: fetchedBooking.check_out,
          });
          
          try {
            const qrUrl = await QRCode.toDataURL(qrData, {
              width: 200,
              margin: 2,
              color: {
                dark: '#0e141b',
                light: '#ffffff',
              },
            });
            setQrCodeDataUrl(qrUrl);
          } catch (error) {
            console.error('Failed to generate QR code:', error);
          }
        }
      } catch (err: any) {
        console.error('Error fetching booking:', err);
        setError(err.message || (isEn ? 'Failed to load booking' : 'Imeshindwa kupakia ombi'));
      } finally {
        setIsLoading(false);
      }
    };

    fetchBooking();
  }, [isAuthenticated, authLoading, router, bookingId, isEn]);

  if (isLoading) {
    return (
      <div className="bg-background-light dark:bg-background-dark min-h-screen flex items-center justify-center">
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

  const calculateNights = (checkIn: string, checkOut: string) => {
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    const diffTime = Math.abs(checkOutDate.getTime() - checkInDate.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  };

  if (isLoading) {
    return (
      <div className="bg-background-light dark:bg-background-dark min-h-screen flex items-center justify-center">
        <div className="text-center">
          <span className="material-symbols-outlined text-6xl text-primary animate-spin mb-4">refresh</span>
          <p className="text-gray-600 dark:text-gray-400">{isEn ? 'Loading...' : 'Inapakia...'}</p>
        </div>
      </div>
    );
  }

  if (error || !booking) {
    return (
      <div className="bg-background-light dark:bg-background-dark min-h-screen flex items-center justify-center">
        <div className="text-center">
          <span className="material-symbols-outlined text-6xl text-red-500 mb-4">error</span>
          <p className="text-xl font-bold mb-2">{error || (isEn ? 'Booking not found' : 'Ombi halijapatikana')}</p>
          <Link href="/my-bookings" className="text-primary hover:underline">
            {isEn ? 'Back to My Bookings' : 'Rudi kwenye Maombi Yangu'}
          </Link>
        </div>
      </div>
    );
  }

  const nights = booking.nights ?? calculateNights(booking.check_in, booking.check_out);
  const roomRate = booking.room_rate ?? ((booking.total_price || booking.total_amount || 0) as number) / nights;
  const totalAmount = (booking.total_price || booking.total_amount || 0) as number;
  const paidAmount = booking.paid_amount ?? 0;
  const balanceDue = booking.balance_due ?? totalAmount;

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-7xl mx-auto px-6 w-full">
        {/* Breadcrumbs */}
        <nav className="flex flex-wrap gap-2 mb-6">
          <Link href="/dashboard" className="text-[#4e7397] dark:text-slate-400 text-sm font-medium hover:text-primary">
            {isEn ? 'Home' : 'Nyumbani'}
          </Link>
          <span className="text-[#4e7397] dark:text-slate-400 text-sm">/</span>
          <Link href="/my-bookings" className="text-[#4e7397] dark:text-slate-400 text-sm font-medium hover:text-primary">
            {isEn ? 'My Bookings' : 'Maombi Yangu'}
          </Link>
          <span className="text-[#4e7397] dark:text-slate-400 text-sm">/</span>
          <span className="text-[#0e141b] dark:text-white text-sm font-medium">#{booking.hashid || booking.id}</span>
        </nav>

        {/* Header Image & Status */}
        <div className="mb-8 overflow-hidden rounded-xl bg-slate-200 dark:bg-slate-800 h-[300px] relative group">
          <div
            className="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105"
            style={{
              backgroundImage: `linear-gradient(0deg, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0) 40%), url('${booking.room?.images?.[0] || 'https://via.placeholder.com/1200x300?text=Room'}')`,
            }}
          />
          <div className="absolute bottom-0 left-0 p-8 w-full flex justify-between items-end">
            <div>
              <span
                className={`inline-block px-3 py-1 text-white text-xs font-bold rounded-full mb-2 ${
                  booking.status === 'confirmed'
                    ? 'bg-green-500'
                    : booking.status === 'online_booking'
                    ? 'bg-purple-500'
                    : 'bg-amber-500'
                }`}
              >
                {booking.status === 'confirmed'
                  ? isEn
                    ? 'CONFIRMED'
                    : 'IMEIDHINISHWA'
                  : booking.status === 'online_booking'
                  ? isEn
                    ? 'ONLINE BOOKING'
                    : 'OMBI LA MTANDAONI'
                  : isEn
                  ? 'PENDING'
                  : 'INASUBIRI'}
              </span>
              <h1 className="text-white text-4xl font-black leading-tight tracking-tight">
                {booking.room?.name || `Room ${booking.room_id}`}
              </h1>
              <p className="text-white/90 text-lg font-normal">
                {isEn ? 'Booking Reference' : 'Nambari ya Ombi'}: #{booking.hashid || booking.id}
              </p>
            </div>
            <div className="hidden md:block">
              <button className="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white border border-white/30 px-6 py-2 rounded-xl font-bold flex items-center gap-2 transition-all">
                <span className="material-symbols-outlined">edit</span>
                {isEn ? 'Modify Booking' : 'Hariri Ombi'}
              </button>
            </div>
          </div>
        </div>

        {/* Layout Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column: Booking Info */}
          <div className="lg:col-span-2 space-y-8">
            {/* Navigation Tabs */}
            <div className="border-b border-[#d0dbe7] dark:border-slate-800">
              <div className="flex gap-8 overflow-x-auto">
                <button className="flex flex-col items-center justify-center border-b-[3px] border-primary text-[#0e141b] dark:text-white pb-[13px] pt-4 whitespace-nowrap">
                  <p className="text-sm font-bold tracking-tight">{isEn ? 'Stay Info' : 'Taarifa za Makao'}</p>
                </button>
                <button className="flex flex-col items-center justify-center border-b-[3px] border-transparent text-[#4e7397] hover:text-primary pb-[13px] pt-4 whitespace-nowrap">
                  <p className="text-sm font-bold tracking-tight">{isEn ? 'Price Details' : 'Maelezo ya Bei'}</p>
                </button>
                <button className="flex flex-col items-center justify-center border-b-[3px] border-transparent text-[#4e7397] hover:text-primary pb-[13px] pt-4 whitespace-nowrap">
                  <p className="text-sm font-bold tracking-tight">{isEn ? 'Policies' : 'Sera'}</p>
                </button>
              </div>
            </div>

            {/* Stay & Room Info Cards */}
            <section className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm">
                <div className="flex items-center gap-3 mb-4 text-primary">
                  <span className="material-symbols-outlined">calendar_today</span>
                  <h3 className="font-bold text-lg">{isEn ? 'Check-in' : 'Kuingia'}</h3>
                </div>
                <p className="text-2xl font-bold">{formatDate(booking.check_in)}</p>
                <p className="text-[#4e7397] dark:text-slate-400">{isEn ? 'After 3:00 PM' : 'Baada ya 3:00 PM'}</p>
              </div>
              <div className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm">
                <div className="flex items-center gap-3 mb-4 text-primary">
                  <span className="material-symbols-outlined">event_available</span>
                  <h3 className="font-bold text-lg">{isEn ? 'Check-out' : 'Kutoka'}</h3>
                </div>
                <p className="text-2xl font-bold">{formatDate(booking.check_out)}</p>
                <p className="text-[#4e7397] dark:text-slate-400">{isEn ? 'Before 11:00 AM' : 'Kabla ya 11:00 AM'}</p>
              </div>
            </section>

            {/* Room Description & Amenities */}
            <section className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm">
              <h3 className="font-bold text-xl mb-4">{isEn ? 'Room Details' : 'Maelezo ya Chumba'}</h3>
              <div className="flex flex-wrap gap-4 mb-6">
                <div className="flex items-center gap-2 bg-background-light dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                  <span className="material-symbols-outlined text-sm">wifi</span>
                  <span className="text-sm font-medium">{isEn ? 'Free WiFi' : 'WiFi Bure'}</span>
                </div>
                <div className="flex items-center gap-2 bg-background-light dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                  <span className="material-symbols-outlined text-sm">coffee</span>
                  <span className="text-sm font-medium">{isEn ? 'Breakfast Included' : 'Chakula cha Asubuhi Imepakiwa'}</span>
                </div>
                <div className="flex items-center gap-2 bg-background-light dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                  <span className="material-symbols-outlined text-sm">king_bed</span>
                  <span className="text-sm font-medium">{isEn ? 'King Bed' : 'Kitanda cha Mfalme'}</span>
                </div>
                <div className="flex items-center gap-2 bg-background-light dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                  <span className="material-symbols-outlined text-sm">ac_unit</span>
                  <span className="text-sm font-medium">{isEn ? 'Air Conditioning' : 'Kiyoyozi'}</span>
                </div>
              </div>
              <p className="text-[#4e7397] dark:text-slate-300 leading-relaxed">
                {isEn
                  ? 'Enjoy luxury in our spacious room. Features modern amenities and comfortable furnishings for a relaxing stay.'
                  : 'Furahia anasa katika chumba chetu chenye nafasi. Kina vifaa vya kisasa na samani za starehe kwa makao ya kupumzika.'}
              </p>
            </section>

            {/* Financial Breakdown */}
            <section className="bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
              <div className="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 className="font-bold text-xl">{isEn ? 'Financial Summary' : 'Muhtasari wa Fedha'}</h3>
              </div>
              <div className="p-6 space-y-4">
                <div className="flex justify-between items-center">
                  <span className="text-[#4e7397] dark:text-slate-400">{isEn ? 'Duration' : 'Muda'}</span>
                  <span className="font-medium">{nights} {isEn ? (nights === 1 ? 'night' : 'nights') : nights === 1 ? 'usiku' : 'usiku'}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-[#4e7397] dark:text-slate-400">{isEn ? 'Rate per Night' : 'Kiwango cha Usiku'}</span>
                  <span className="font-medium">TZS {roomRate.toLocaleString()}</span>
                </div>
                <div className="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                  <span className="text-lg font-bold">{isEn ? 'Total Amount' : 'Jumla ya Kiasi'}</span>
                  <span className="text-lg font-bold">TZS {totalAmount.toLocaleString()}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-[#4e7397] dark:text-slate-400">{isEn ? 'Paid Amount' : 'Kiasi Kilicholipwa'}</span>
                  <span className="font-medium text-green-600">TZS {paidAmount.toLocaleString()}</span>
                </div>
                <div className="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                  <span className="text-lg font-bold">{isEn ? 'Balance Due' : 'Deni Lililobaki'}</span>
                  <span className="text-2xl font-black text-primary">TZS {balanceDue.toLocaleString()}</span>
                </div>
              </div>
            </section>

            {/* Payment History */}
            <section className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm">
              <h3 className="font-bold text-xl mb-4">{isEn ? 'Payment History' : 'Historia ya Malipo'}</h3>
              {booking.payment_history && booking.payment_history.length > 0 ? (
                <div className="space-y-4">
                  {booking.payment_history.map((payment) => (
                    <div key={payment.id} className="flex items-center justify-between py-2">
                      <div className="flex items-center gap-4">
                        <div className="bg-primary/10 p-2 rounded-lg text-primary">
                          <span className="material-symbols-outlined">credit_card</span>
                        </div>
                        <div>
                          <p className="font-bold">{payment.type}</p>
                          <p className="text-sm text-[#4e7397] dark:text-slate-400">
                            {formatDate(payment.date)} • {payment.description || (isEn ? 'Payment' : 'Malipo')}
                          </p>
                          {payment.bank_account && (
                            <p className="text-xs text-[#4e7397] dark:text-slate-400">{payment.bank_account}</p>
                          )}
                        </div>
                      </div>
                      <span className="font-bold text-green-600">TZS {payment.amount.toLocaleString()}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-[#4e7397] dark:text-slate-400">
                  <span className="material-symbols-outlined text-4xl mb-2">payment</span>
                  <p>{isEn ? 'No payment history available' : 'Hakuna historia ya malipo'}</p>
                </div>
              )}
            </section>

            {/* Cancellation Policy */}
            <section className="bg-amber-50 dark:bg-amber-900/20 p-6 rounded-xl border border-amber-100 dark:border-amber-800/30">
              <div className="flex items-start gap-4">
                <span className="material-symbols-outlined text-amber-600 dark:text-amber-500">info</span>
                <div>
                  <h3 className="font-bold text-amber-800 dark:text-amber-400 mb-1">
                    {isEn ? 'Cancellation Policy' : 'Sera ya Kughairi'}
                  </h3>
                  <p className="text-amber-700 dark:text-amber-500 text-sm leading-relaxed">
                    {isEn
                      ? 'Free cancellation until 24 hours before check-in. Cancellations after this date or no-shows are subject to a fee of one night\'s stay plus taxes.'
                      : 'Kughairi bure hadi masaa 24 kabla ya kuingia. Kughairi baada ya tarehe hii au kutokuja kunalipa ada ya usiku mmoja pamoja na kodi.'}
                  </p>
                </div>
              </div>
            </section>
          </div>

          {/* Right Column: Sidebar Actions & QR */}
          <div className="space-y-6">
            {/* Digital Check-in QR Code */}
            <div className="bg-white dark:bg-slate-900 p-8 rounded-xl border border-slate-100 dark:border-slate-800 shadow-lg text-center">
              <h3 className="font-bold text-xl mb-2">{isEn ? 'Digital Check-in' : 'Kuingia Dijitali'}</h3>
              <p className="text-sm text-[#4e7397] dark:text-slate-400 mb-6">
                {isEn
                  ? 'Scan this QR code at the reception kiosk for fast check-in.'
                  : 'Changanua msimbo huu wa QR kwenye kioski ya mapokezi kwa kuingia haraka.'}
              </p>
              <div className="mx-auto bg-white p-4 rounded-xl border-2 border-primary/20 inline-block mb-6 shadow-inner">
                {qrCodeDataUrl ? (
                  <img 
                    src={qrCodeDataUrl} 
                    alt="QR Code" 
                    className="size-48"
                  />
                ) : (
                <div className="size-48 bg-slate-100 flex items-center justify-center relative">
                  <div className="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-primary to-transparent"></div>
                  <span className="material-symbols-outlined text-primary scale-[4]">qr_code_2</span>
                </div>
                )}
              </div>
              <div className="flex items-center justify-center gap-2 text-primary font-bold">
                <span className="material-symbols-outlined">lock</span>
                <span>{isEn ? 'Secure Pass' : 'Pasi Salama'}</span>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm space-y-3">
              <button
                onClick={async () => {
                  if (!booking.hashid) return;
                  setIsDownloading(true);
                  try {
                    await bookingsAPI.downloadReceipt(booking.hashid);
                  } catch (err: any) {
                    alert(err.message || (isEn ? 'Failed to download receipt' : 'Imeshindwa kupakua risiti'));
                  } finally {
                    setIsDownloading(false);
                  }
                }}
                disabled={isDownloading || !booking.hashid}
                className="w-full bg-primary hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-colors"
              >
                <span className="material-symbols-outlined">{isDownloading ? 'hourglass_empty' : 'download'}</span>
                {isDownloading ? (isEn ? 'Downloading...' : 'Inapakua...') : isEn ? 'Download Receipt (PDF)' : 'Pakua Risiti (PDF)'}
              </button>
              {booking.status === 'pending' || booking.status === 'online_booking' ? (
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
                        await bookingsAPI.cancelBooking(booking.hashid || booking.id);
                        await Swal.fire({
                          title: isEn ? 'Cancelled!' : 'Imeghairiwa!',
                          text: isEn ? 'Your booking has been cancelled successfully.' : 'Ombi lako limeghairiwa kwa mafanikio.',
                          icon: 'success',
                          confirmButtonColor: '#2563eb',
                        });
                        router.push('/my-bookings');
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
                  className="w-full text-red-600 dark:text-red-400 py-3 rounded-xl font-bold hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors"
                >
                  {isEn ? 'Cancel Booking' : 'Ghairi Ombi'}
                </button>
              ) : null}
            </div>

            {/* Hotel Contact Details */}
            {companySettings && (
              <div className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm">
                <h3 className="font-bold text-lg mb-4">{isEn ? 'Hotel Information' : 'Taarifa za Hoteli'}</h3>
                <div className="space-y-4">
                  <div className="flex items-start gap-3">
                    <span className="material-symbols-outlined text-primary text-sm mt-1">location_on</span>
                    <div className="text-sm">
                      <p className="font-bold">{companySettings.name}</p>
                      <p className="text-[#4e7397] dark:text-slate-400">{companySettings.address}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="material-symbols-outlined text-primary text-sm">phone</span>
                    <p className="text-sm font-medium">{companySettings.phone}</p>
                  </div>
                  {companySettings.email && (
                    <div className="flex items-center gap-3">
                      <span className="material-symbols-outlined text-primary text-sm">mail</span>
                      <p className="text-sm font-medium">{companySettings.email}</p>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}
