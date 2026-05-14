'use client';

import React from 'react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useLanguage } from '@/contexts/LanguageContext';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import { roomsAPI, Room } from '@/lib/api/rooms';

export default function RoomsPage() {
  const { language } = useLanguage();
  const { isAuthenticated } = useAuth();
  const isEn = language === 'en';
  const [rooms, setRooms] = React.useState<Room[]>([]);
  const [isLoading, setIsLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [currentPage, setCurrentPage] = React.useState(1);
  const [hasMore, setHasMore] = React.useState(false);
  const [isLoadingMore, setIsLoadingMore] = React.useState(false);
  const [searchParams, setSearchParams] = React.useState<{
    checkIn?: string;
    checkOut?: string;
    adults?: string;
    children?: string;
  }>({});

  const buildBookingUrl = (room: Room) => {
    const basePath = `/book/${room.hashid || room.id}`;
    if (!searchParams.checkIn || !searchParams.checkOut) {
      return basePath;
    }
    const params = new URLSearchParams({
      checkIn: searchParams.checkIn,
      checkOut: searchParams.checkOut,
      adults: searchParams.adults || '2',
      children: searchParams.children || '0',
    });
    return `${basePath}?${params.toString()}`;
  };

  const handleBookRoom = (room: Room) => {
    if (!isAuthenticated) {
      // Save booking data before redirecting to login
      if (searchParams.checkIn && searchParams.checkOut) {
        const bookingData = {
          room_id: room.id,
          room_hashid: room.hashid || null,
          checkIn: searchParams.checkIn,
          checkOut: searchParams.checkOut,
          adults: searchParams.adults || '2',
          children: searchParams.children || '0',
        };
        console.log('Saving booking data:', bookingData);
        sessionStorage.setItem('pending_booking', JSON.stringify(bookingData));
        console.log('Booking data saved to sessionStorage');
      }
      // Save current page URL
      if (typeof window !== 'undefined') {
        sessionStorage.setItem('redirect_url', window.location.pathname + window.location.search);
      }
    }
  };

  React.useEffect(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const checkIn = params.get('checkIn') || undefined;
      const checkOut = params.get('checkOut') || undefined;
      const adults = params.get('adults') || '2';
      const children = params.get('children') || '0';
      const branchId = params.get('branchId') || undefined;
      
      setSearchParams({
        checkIn,
        checkOut,
        adults,
        children,
        branchId,
      });
    }
  }, []);

  React.useEffect(() => {
    let isMounted = true;
    let timeoutId: NodeJS.Timeout;
    
    const fetchRooms = async () => {
      // Clear any pending timeouts
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      
      setIsLoading(true);
      setError(null);
      setCurrentPage(1);
      
      try {
        // Always use getAvailableRooms when dates are provided
        const branchId = searchParams.branchId ? Number(searchParams.branchId) : undefined;
        const result = searchParams.checkIn && searchParams.checkOut
          ? await roomsAPI.getAvailableRooms(searchParams.checkIn, searchParams.checkOut, 1, 12, branchId)
          : await roomsAPI.getAllRooms(1, 12);
        
        // Only update state if component is still mounted
        if (isMounted) {
          // Preserve status exactly as returned from API
          const roomsWithStatus = result.rooms.map(room => {
            // Log for debugging
            if (process.env.NODE_ENV === 'development') {
              console.log(`[Rooms] Room ${room.id} (${room.name}): status = ${room.status}`);
            }
            return {
              ...room,
              // Preserve the status exactly as returned from API
              status: (room.status || 'available') as 'available' | 'booked' | 'maintenance' | 'out_of_order',
            };
          });
          
          // Single state update to prevent flickering
          setRooms(roomsWithStatus);
          setHasMore(result.pagination ? result.pagination.current_page < result.pagination.last_page : false);
        }
      } catch (err: any) {
        console.error('Error fetching rooms:', err);
        if (isMounted) {
          setError(err.message || (isEn ? 'Failed to load rooms' : 'Imeshindwa kupakia vyumba'));
          setRooms([]);
          setHasMore(false);
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    // Debounce to prevent multiple rapid calls
    timeoutId = setTimeout(() => {
      fetchRooms();
    }, 100);

    return () => {
      isMounted = false;
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
  }, [searchParams.checkIn, searchParams.checkOut]);

  const loadMoreRooms = async () => {
    if (isLoadingMore || !hasMore) return;
    
    setIsLoadingMore(true);
    try {
      const nextPage = currentPage + 1;
      const result = searchParams.checkIn && searchParams.checkOut
        ? await roomsAPI.getAvailableRooms(searchParams.checkIn, searchParams.checkOut, nextPage, 12)
        : await roomsAPI.getAllRooms(nextPage, 12);
      
      setRooms((prev) => [...prev, ...result.rooms]);
      setCurrentPage(nextPage);
      setHasMore(result.pagination ? result.pagination.current_page < result.pagination.last_page : false);
    } catch (err: any) {
      console.error('Error loading more rooms:', err);
    } finally {
      setIsLoadingMore(false);
    }
  };

  const formatDate = (dateStr?: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString(isEn ? 'en-US' : 'sw-TZ', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  const calculateNights = () => {
    if (!searchParams.checkIn || !searchParams.checkOut) return 0;
    const checkIn = new Date(searchParams.checkIn);
    const checkOut = new Date(searchParams.checkOut);
    const diffTime = Math.abs(checkOut.getTime() - checkIn.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  const nights = calculateNights();

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden bg-background-light dark:bg-background-dark">
      <Header />
      <main className="flex-1 flex flex-col pt-16">
        {/* Hero Section */}
        <div className="relative w-full h-[400px] flex flex-col items-center justify-center px-4 overflow-hidden">
          <div
            className="absolute inset-0 bg-cover bg-center"
            style={{
              backgroundImage: `url('https://lh3.googleusercontent.com/aida-public/AB6AXuBVznD9JHy9z3LU9emGvERM0JOHXlUSp8poNWZJk_yuF43Ch1dndDEBukM6cbpoiHBaLT-6wZwPK03dI8t-iyVbwmxe4tFt4WQbyowfKoVsFIWrDC4_gPZOQ_t_FqKsiazKHmRP_mIRHmuhZV5E1HQmYEFV2-NqBUg-SjFtAbRZToJD8vftH958LU3hADUNlveylG2RjpolbLq5XNTtn3u3k-kofHwOxTfbKBBNd3Pxk53BpcWg_MapXbOIaHXKbKG5atNChupI8SE')`,
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-b from-[rgba(16,28,34,0.7)] to-[rgba(16,28,34,0.4)] blur-sm"></div>
          </div>
          <div className="relative z-10 text-center">
            <h2 className="text-white text-4xl md:text-5xl font-black mb-4 drop-shadow-lg tracking-tight">
              {isEn ? 'Available Rooms' : 'Vyumba Vinavyopatikana'}
            </h2>
            <p className="text-white/90 text-lg md:text-xl font-medium max-w-2xl mx-auto drop-shadow-md">
              {isEn
                ? 'Discover our carefully curated selection of luxurious accommodations designed for your comfort.'
                : 'Gundua uteuzi wetu wa makini wa malazi ya anasa yaliyoundwa kwa starehe yako.'}
            </p>
          </div>
        </div>

        {/* Content Section */}
        <div className="relative z-20 -mt-20 max-w-[1200px] mx-auto w-full px-4 lg:px-10 pb-12">
          {/* Search Criteria Summary */}
          {searchParams.checkIn && searchParams.checkOut && (
            <div className="mb-8">
              <div className="flex flex-col md:flex-row items-center justify-between gap-4 p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-[#1a2b34] shadow-sm">
                <div className="flex items-center gap-4">
                  <div className="flex items-center justify-center size-10 rounded-full bg-primary/10 text-primary">
                    <span className="material-symbols-outlined">calendar_month</span>
                  </div>
                  <div>
                    <p className="text-base font-bold leading-tight">
                      {formatDate(searchParams.checkIn)} - {formatDate(searchParams.checkOut)}
                    </p>
                    <p className="text-[#4c809a] dark:text-slate-400 text-sm font-normal">
                      {isEn
                        ? `${searchParams.adults} Adult${Number(searchParams.adults) > 1 ? 's' : ''}${
                            Number(searchParams.children) > 0
                              ? `, ${searchParams.children} Child${Number(searchParams.children) > 1 ? 'ren' : ''}`
                              : ''
                          } • ${nights} Night${nights > 1 ? 's' : ''}`
                        : `Watu wazima ${searchParams.adults}${
                            Number(searchParams.children) > 0 ? `, Watoto ${searchParams.children}` : ''
                          } • Usiku ${nights}`}
                    </p>
                  </div>
                </div>
                <Link
                  href="/"
                  className="flex items-center gap-2 text-sm font-bold text-primary hover:underline group"
                >
                  {isEn ? 'Edit Search' : 'Hariri Utafutaji'}
                  <span className="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </Link>
              </div>
            </div>
          )}

          {/* Page Header */}
          <div className="bg-white dark:bg-[#1a2b34] rounded-xl shadow-lg p-6 mb-6">
            <div className="flex flex-col gap-2">
              <h2 className="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                {isEn ? 'Room Selection' : 'Uchaguzi wa Vyumba'}
              </h2>
              <p className="text-[#4c809a] dark:text-slate-400">
                {isEn
                  ? 'Showing 4 types of rooms at Seaside Resort & Spa'
                  : 'Inaonyesha aina 4 za vyumba katika Seaside Resort & Spa'}
              </p>
            </div>
          </div>

          {/* Loading State */}
          {isLoading && (
            <div className="text-center py-12">
              <span className="material-symbols-outlined text-6xl text-primary animate-spin mb-4">refresh</span>
              <p className="text-gray-600 dark:text-gray-400">
                {isEn ? 'Loading rooms...' : 'Inapakia vyumba...'}
              </p>
            </div>
          )}

          {/* Error State */}
          {error && !isLoading && (
            <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-lg mb-6">
              {error}
            </div>
          )}

          {/* Rooms List */}
          {!isLoading && !error && (
            <div className="flex flex-col gap-6">
              {rooms.length === 0 ? (
                <div className="text-center py-12">
                  <span className="material-symbols-outlined text-6xl text-gray-400 mb-4">hotel</span>
                  <p className="text-lg font-bold mb-2">
                    {isEn ? 'No rooms available' : 'Hakuna vyumba vinavyopatikana'}
                  </p>
                  <p className="text-gray-600 dark:text-gray-400">
                    {isEn
                      ? 'Please try different search criteria'
                      : 'Tafadhali jaribu vigezo vingine vya utafutaji'}
                  </p>
                </div>
              ) : (
                rooms.map((room) => (
                  <div
                    key={room.id}
                    className={`flex flex-col lg:flex-row items-stretch overflow-hidden rounded-xl bg-white dark:bg-[#1a2b34] shadow-md border transition-all ${
                      room.status !== 'available'
                        ? 'border-slate-200 dark:border-slate-700 grayscale opacity-80'
                        : 'border-transparent hover:border-primary/30'
                    }`}
                  >
                {/* Room Image */}
                <div className="lg:w-1/3 min-h-[240px] relative">
                  <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{
                      backgroundImage: `url('${room.images && room.images.length > 0 ? room.images[0] : 'https://via.placeholder.com/400x300?text=Room'}'`,
                    }}
                  />
                  {room.status === 'available' && (
                    <div className="absolute top-4 left-4 bg-white/90 dark:bg-black/70 backdrop-blur-sm px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest flex items-center gap-1">
                      <span className="material-symbols-outlined text-[14px] text-green-500">check_circle</span>
                      {isEn ? 'Available' : 'Inapatikana'}
                    </div>
                  )}
                  {(room.status === 'booked' || room.status === 'out_of_order') && (
                    <div className="absolute inset-0 bg-black/40 flex items-center justify-center">
                      <span className="text-white font-black text-2xl uppercase tracking-[0.2em] -rotate-12 border-4 border-white px-4 py-1">
                        {room.status === 'booked'
                          ? isEn
                            ? 'Booked'
                            : 'Imeombwa'
                          : isEn
                          ? 'Out of Order'
                          : 'Haifanyi Kazi'}
                      </span>
                    </div>
                  )}
                </div>

                {/* Room Details */}
                <div className="flex-1 p-6 flex flex-col md:flex-row gap-6">
                  <div className="flex-1 flex flex-col justify-between">
                    <div>
                      {/* Status Badge */}
                      {room.status === 'available' && (
                        <div className="flex items-center gap-2 mb-1">
                          <span className="text-primary text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                            <span className="material-symbols-outlined text-[16px]">check_circle</span>
                            {isEn ? 'Available Now' : 'Inapatikana Sasa'}
                          </span>
                        </div>
                      )}
                      {room.status === 'booked' && (
                        <div className="flex items-center gap-2 mb-1">
                          <span className="text-red-500 text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                            <span className="material-symbols-outlined text-[16px]">block</span>
                            {isEn ? 'Booked' : 'Imeombwa'}
                          </span>
                        </div>
                      )}

                      <h3 className="text-xl font-bold mb-2">{room.name}</h3>
                      {room.room_number && (
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                          {isEn ? `Room ${room.room_number}` : `Chumba ${room.room_number}`}
                        </p>
                      )}
                      <p className="flex items-center gap-2 text-[#4c809a] dark:text-slate-400 text-sm mb-4">
                        <span className="material-symbols-outlined text-[18px]">group</span>
                        {isEn
                          ? `Max: ${room.max_adults} Adult${room.max_adults > 1 ? 's' : ''}${
                              room.max_children > 0
                                ? `, ${room.max_children} Child${room.max_children > 1 ? 'ren' : ''}`
                                : ''
                            }`
                          : `Kiwango cha juu: Watu wazima ${room.max_adults}${
                              room.max_children > 0 ? `, Watoto ${room.max_children}` : ''
                            }`}
                      </p>

                      {/* Amenities */}
                      {room.amenities && room.amenities.length > 0 && (
                        <div className="flex flex-wrap gap-x-4 gap-y-2">
                          {room.amenities.map((amenity, index) => (
                            <span
                              key={index}
                              className={`flex items-center gap-1.5 text-xs bg-background-light dark:bg-slate-800 px-2 py-1 rounded ${
                                room.status !== 'available'
                                  ? 'text-slate-400 dark:text-slate-500'
                                  : 'text-slate-600 dark:text-slate-300'
                              }`}
                            >
                              <span className="material-symbols-outlined text-[16px] text-primary">
                                {amenity.toLowerCase().includes('wifi') ||
                                amenity.toLowerCase().includes('internet')
                                  ? 'wifi'
                                  : amenity.toLowerCase().includes('ac') ||
                                    amenity.toLowerCase().includes('air')
                                  ? 'ac_unit'
                                  : amenity.toLowerCase().includes('tv') ||
                                    amenity.toLowerCase().includes('television')
                                  ? 'tv'
                                  : amenity.toLowerCase().includes('bath')
                                  ? 'bathtub'
                                  : amenity.toLowerCase().includes('balcony')
                                  ? 'balcony'
                                  : amenity.toLowerCase().includes('bar')
                                  ? 'kitchen'
                                  : amenity.toLowerCase().includes('shower')
                                  ? 'shower'
                                  : amenity.toLowerCase().includes('workspace') ||
                                    amenity.toLowerCase().includes('desk')
                                  ? 'desk'
                                  : amenity.toLowerCase().includes('coffee') ||
                                    amenity.toLowerCase().includes('nespresso')
                                  ? 'coffee_maker'
                                  : 'check'}
                              </span>
                              {amenity}
                            </span>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Price and Booking */}
                  <div className="w-full md:w-48 flex flex-col justify-center items-end border-t md:border-t-0 md:border-l border-[#e7eff3] dark:border-slate-700 pt-4 md:pt-0 md:pl-6">
                    <p className="text-[#4c809a] dark:text-slate-400 text-xs text-right">
                      {isEn ? 'Price per night' : 'Bei kwa usiku'}
                    </p>
                    <p
                      className={`text-2xl font-extrabold ${
                        room.status !== 'available'
                          ? 'text-slate-400'
                          : 'text-[#0d171b] dark:text-white'
                      }`}
                    >
                      TZS {room.price.toLocaleString()}
                    </p>
                    <p className="text-xs text-[#4c809a] dark:text-slate-400 mb-4 text-right">
                      {room.status !== 'available'
                        ? isEn
                          ? 'Not available'
                          : 'Haipatikani'
                        : isEn
                        ? 'Includes taxes & fees'
                        : 'Inajumuisha kodi na ada'}
                    </p>
                    <div className="flex flex-col gap-2 w-full">
                      {room.status !== 'available' ? (
                        <button
                          disabled
                          className="w-full bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 font-bold py-2.5 rounded-lg cursor-not-allowed"
                        >
                          {room.status === 'booked'
                            ? isEn
                              ? 'Booked'
                              : 'Imeombwa'
                            : isEn
                            ? 'Not Available'
                            : 'Haipatikani'}
                        </button>
                      ) : (
                        <Link
                          href={
                            isAuthenticated
                              ? buildBookingUrl(room)
                              : '/login'
                          }
                          onClick={() => handleBookRoom(room)}
                          className="w-full bg-primary text-white font-bold py-2.5 rounded-lg flex items-center justify-center gap-2 hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all"
                        >
                          {isEn ? 'Book Now' : 'Omba Sasa'}
                          <span className="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </Link>
                      )}
                      <Link
                        href={`/admin/rooms/${room.hashid || room.id}`}
                        className="w-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold py-2.5 rounded-lg flex items-center justify-center gap-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700"
                      >
                        <span className="material-symbols-outlined text-[18px]">info</span>
                        {isEn ? 'Room Details' : 'Maelezo ya Chumba'}
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
                ))
              )}
            </div>
          )}

          {/* Load More */}
          {hasMore && (
            <div className="mt-12 flex justify-center">
              <button
                onClick={loadMoreRooms}
                disabled={isLoadingMore}
                className="px-8 py-3 border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {isLoadingMore ? (
                  <>
                    <span className="material-symbols-outlined animate-spin">refresh</span>
                    <span>{isEn ? 'Loading...' : 'Inapakia...'}</span>
                  </>
                ) : (
                  <>
                    <span>{isEn ? 'Show More Results' : 'Onyesha Matokeo Zaidi'}</span>
                    <span className="material-symbols-outlined">expand_more</span>
                  </>
                )}
              </button>
            </div>
          )}
        </div>
      </main>
      <Footer />
    </div>
  );
}
