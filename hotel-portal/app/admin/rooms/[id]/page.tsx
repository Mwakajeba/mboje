'use client';

import React, { useState, useEffect } from 'react';
import { useLanguage } from '@/contexts/LanguageContext';
import { roomsAPI, Room } from '@/lib/api/rooms';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth } from '@/contexts/AuthContext';

export default function RoomDetailsPage({ params }: { params: { id: string } }) {
  const { language } = useLanguage();
  const { isAuthenticated } = useAuth();
  const isEn = language === 'en';
  const router = useRouter();
  const [room, setRoom] = useState<Room | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // This is a read-only page for guests - no editing allowed
  const isReadOnly = true;

  useEffect(() => {
    const fetchRoom = async () => {
      try {
        const roomData = await roomsAPI.getRoomById(params.id);
        setRoom(roomData);
      } catch (err: any) {
        console.error('Error fetching room:', err);
        setError(err.message || (isEn ? 'Failed to load room' : 'Imeshindwa kupakia chumba'));
      } finally {
        setIsLoading(false);
      }
    };

    fetchRoom();
  }, [params.id, isEn]);

  const [roomData, setRoomData] = useState({
    name: '',
    type: 'standard',
    price: '0',
    status: 'available',
    maxAdults: 2,
    maxChildren: 0,
    amenities: [] as string[],
  });

  useEffect(() => {
    if (room) {
      setRoomData({
        name: room.name,
        type: room.type,
        price: room.price.toString(),
        status: room.status,
        maxAdults: room.max_adults,
        maxChildren: room.max_children,
        amenities: room.amenities || [],
      });
    }
  }, [room]);

  const photos = room?.images && room.images.length > 0 
    ? room.images 
    : ['https://via.placeholder.com/800x600?text=No+Image'];

  const allAmenities = [
    { id: 'wifi', label: isEn ? 'High-speed WiFi' : 'WiFi ya Kasi ya Juu' },
    { id: 'minibar', label: isEn ? 'Mini Bar' : 'Mini Bar' },
    { id: 'balcony', label: isEn ? 'Balcony' : 'Balkoni' },
    { id: 'ac', label: isEn ? 'Air Conditioning' : 'Air Conditioning' },
    { id: 'city_view', label: isEn ? 'City View' : 'Mwonekano wa Jiji' },
    { id: 'tv', label: isEn ? 'Smart TV' : 'TV ya Smart' },
    { id: 'room_service', label: isEn ? 'Room Service' : 'Huduma ya Chumbani' },
    { id: 'bathtub', label: isEn ? 'Bathtub' : 'Bafu' },
  ];

  const roomTypes = [
    { value: 'standard', label: isEn ? 'Standard Room' : 'Chumba cha Standard' },
    { value: 'deluxe', label: isEn ? 'Deluxe Room' : 'Chumba cha Deluxe' },
    { value: 'suite', label: isEn ? 'Executive Suite' : 'Suite ya Executive' },
    { value: 'penthouse', label: isEn ? 'Presidential Penthouse' : 'Penthouse ya Rais' },
    { value: 'family', label: isEn ? 'Family Room' : 'Chumba cha Familia' },
  ];

  const toggleAmenity = (amenityId: string) => {
    setRoomData((prev) => ({
      ...prev,
      amenities: prev.amenities.includes(amenityId)
        ? prev.amenities.filter((a) => a !== amenityId)
        : [...prev.amenities, amenityId],
    }));
  };

  if (isLoading) {
    return (
      <div className="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex items-center justify-center">
        <div className="text-center">
          <span className="material-symbols-outlined text-6xl text-primary animate-spin mb-4">refresh</span>
          <p className="text-gray-600 dark:text-gray-400">
            {isEn ? 'Loading room details...' : 'Inapakia maelezo ya chumba...'}
          </p>
        </div>
      </div>
    );
  }

  if (error || !room) {
    return (
      <div className="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex items-center justify-center">
        <div className="text-center">
          <span className="material-symbols-outlined text-6xl text-red-500 mb-4">error</span>
          <p className="text-lg font-bold mb-2">
            {isEn ? 'Room not found' : 'Chumba hakijapatikana'}
          </p>
          <p className="text-gray-600 dark:text-gray-400 mb-4">{error}</p>
          <button
            onClick={() => router.push('/rooms')}
            className="text-primary hover:underline"
          >
            {isEn ? 'Back to Rooms' : 'Rudi kwenye Vyumba'}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
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
              {isEn ? 'Room Details' : 'Maelezo ya Chumba'}
            </h2>
            <p className="text-white/90 text-lg md:text-xl font-medium max-w-2xl mx-auto drop-shadow-md">
              {isEn
                ? 'View and manage room information, images, and amenities.'
                : 'Angalia na simamia taarifa za chumba, picha, na vifaa.'}
            </p>
          </div>
        </div>

        {/* Content Section */}
        <section className="relative z-20 -mt-20 max-w-7xl mx-auto w-full px-6 mb-20">
          <div className="bg-white dark:bg-background-dark rounded-2xl shadow-2xl overflow-hidden p-6 md:p-8">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Room Media Section */}
            <section className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
              <div className="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-primary">photo_library</span>
                  <h3 className="text-slate-900 dark:text-white text-xl font-bold">
                    {isEn ? 'Room Media' : 'Media ya Chumba'}
                  </h3>
                </div>
                  <span className="text-xs font-semibold text-slate-400 uppercase tracking-widest">
                    {room?.images?.length || photos.length} / 10 {isEn ? 'Photos' : 'Picha'}
                  </span>
              </div>
              <div className="space-y-4">
                {/* Main Hero Image */}
                <div className="relative w-full h-[400px] rounded-xl overflow-hidden group">
                  {photos[0] && (
                    <Image
                      src={photos[0]}
                      alt="Main Room View"
                      fill
                      className="object-cover"
                      unoptimized={photos[0].startsWith('http://127.0.0.1') || photos[0].startsWith('http://localhost')}
                    />
                  )}
                  {!isReadOnly && (
                    <div className="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                      <button className="bg-white text-slate-900 px-4 py-2 rounded-lg font-bold flex items-center gap-2 shadow-lg">
                        <span className="material-symbols-outlined text-sm">edit</span>
                        {isEn ? 'Change Cover' : 'Badilisha Kifuniko'}
                      </button>
                    </div>
                  )}
                  <div className="absolute top-4 left-4 bg-primary text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase">
                    {isEn ? 'Main Hero' : 'Picha Kuu'}
                  </div>
                </div>

                {/* Thumbnail Grid */}
                <div className="grid grid-cols-5 gap-4">
                  {photos.slice(1, 5).map((photo, index) => (
                    <div key={index} className="aspect-square rounded-lg overflow-hidden relative group">
                      <Image 
                        src={photo} 
                        alt={`Thumbnail ${index + 1}`} 
                        fill 
                        className="object-cover"
                        unoptimized={photo.startsWith('http://127.0.0.1') || photo.startsWith('http://localhost')}
                      />
                      {!isReadOnly && (
                        <button className="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                          <span className="material-symbols-outlined text-xs">delete</span>
                        </button>
                      )}
                    </div>
                  ))}
                  {photos.length === 0 && (
                    <div className="col-span-5 aspect-square rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex flex-col items-center justify-center gap-1">
                      <span className="material-symbols-outlined text-slate-400 text-4xl">image</span>
                      <span className="text-sm font-bold text-slate-400">{isEn ? 'No Images' : 'Hakuna Picha'}</span>
                    </div>
                  )}
                </div>
              </div>
            </section>

            {/* General Details Section */}
            <section className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
              <div className="flex items-center gap-2 mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                <span className="material-symbols-outlined text-primary">bed</span>
                <h3 className="text-slate-900 dark:text-white text-xl font-bold">
                  {isEn ? 'General Details' : 'Maelezo ya Jumla'}
                </h3>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label className="flex flex-col gap-2">
                  <span className="text-slate-700 dark:text-slate-300 text-sm font-semibold">
                    {isEn ? 'Room Name / Number *' : 'Jina / Nambari ya Chumba *'}
                  </span>
                  <input
                    className="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-primary focus:border-primary h-12 px-4"
                    placeholder={isEn ? 'e.g. Suite 402' : 'mfano. Suite 402'}
                    type="text"
                    value={roomData.name}
                    onChange={(e) => !isReadOnly && setRoomData({ ...roomData, name: e.target.value })}
                    readOnly={isReadOnly}
                    disabled={isReadOnly}
                    required
                  />
                </label>
                <label className="flex flex-col gap-2">
                  <span className="text-slate-700 dark:text-slate-300 text-sm font-semibold">
                    {isEn ? 'Room Type *' : 'Aina ya Chumba *'}
                  </span>
                  <select
                    className="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-primary focus:border-primary h-12 px-4"
                    value={roomData.type}
                    onChange={(e) => !isReadOnly && setRoomData({ ...roomData, type: e.target.value })}
                    disabled={isReadOnly}
                    required
                  >
                    {roomTypes.map((type) => (
                      <option key={type.value} value={type.value}>
                        {type.label}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="flex flex-col gap-2">
                  <span className="text-slate-700 dark:text-slate-300 text-sm font-semibold">
                    {isEn ? 'Base Price per Night ($) *' : 'Bei ya Msingi kwa Usiku ($) *'}
                  </span>
                  <input
                    className="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-primary focus:border-primary h-12 px-4"
                    placeholder="0.00"
                    type="number"
                    step="0.01"
                    value={roomData.price}
                    onChange={(e) => !isReadOnly && setRoomData({ ...roomData, price: e.target.value })}
                    readOnly={isReadOnly}
                    disabled={isReadOnly}
                    required
                  />
                </label>
                <label className="flex flex-col gap-2">
                  <span className="text-slate-700 dark:text-slate-300 text-sm font-semibold">
                    {isEn ? 'Room Status *' : 'Hali ya Chumba *'}
                  </span>
                  <select
                    className="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-primary focus:border-primary h-12 px-4"
                    value={roomData.status}
                    onChange={(e) => !isReadOnly && setRoomData({ ...roomData, status: e.target.value })}
                    disabled={isReadOnly}
                    required
                  >
                    <option value="available">{isEn ? 'Available' : 'Inapatikana'}</option>
                    <option value="maintenance">{isEn ? 'Maintenance' : 'Matengenezo'}</option>
                    <option value="out_of_order">{isEn ? 'Out of Order' : 'Haifanyi Kazi'}</option>
                  </select>
                </label>
                <div className="flex flex-col gap-2">
                  <span className="text-slate-700 dark:text-slate-300 text-sm font-semibold">
                    {isEn ? 'Max Adults' : 'Watu Wazima Wengi'}
                  </span>
                  <div className="flex items-center gap-0 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden h-12">
                    {!isReadOnly && (
                      <button
                        type="button"
                        onClick={() => setRoomData({ ...roomData, maxAdults: Math.max(1, roomData.maxAdults - 1) })}
                        className="w-12 h-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center border-r border-slate-200 dark:border-slate-700"
                      >
                        <span className="material-symbols-outlined text-sm">remove</span>
                      </button>
                    )}
                    <input
                      className="flex-1 text-center bg-white dark:bg-slate-900 border-none focus:ring-0 text-slate-900 dark:text-white"
                      type="number"
                      min="1"
                      value={roomData.maxAdults}
                      onChange={(e) => !isReadOnly && setRoomData({ ...roomData, maxAdults: parseInt(e.target.value) || 1 })}
                      readOnly={isReadOnly}
                      disabled={isReadOnly}
                    />
                    {!isReadOnly && (
                      <button
                        type="button"
                        onClick={() => setRoomData({ ...roomData, maxAdults: roomData.maxAdults + 1 })}
                        className="w-12 h-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center border-l border-slate-200 dark:border-slate-700"
                      >
                        <span className="material-symbols-outlined text-sm">add</span>
                      </button>
                    )}
                  </div>
                </div>
                <div className="flex flex-col gap-2">
                  <span className="text-slate-700 dark:text-slate-300 text-sm font-semibold">
                    {isEn ? 'Max Children' : 'Watoto Wengi'}
                  </span>
                  <div className="flex items-center gap-0 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden h-12">
                    {!isReadOnly && (
                      <button
                        type="button"
                        onClick={() => setRoomData({ ...roomData, maxChildren: Math.max(0, roomData.maxChildren - 1) })}
                        className="w-12 h-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center border-r border-slate-200 dark:border-slate-700"
                      >
                        <span className="material-symbols-outlined text-sm">remove</span>
                      </button>
                    )}
                    <input
                      className="flex-1 text-center bg-white dark:bg-slate-900 border-none focus:ring-0 text-slate-900 dark:text-white"
                      type="number"
                      min="0"
                      value={roomData.maxChildren}
                      onChange={(e) => !isReadOnly && setRoomData({ ...roomData, maxChildren: parseInt(e.target.value) || 0 })}
                      readOnly={isReadOnly}
                      disabled={isReadOnly}
                    />
                    {!isReadOnly && (
                      <button
                        type="button"
                        onClick={() => setRoomData({ ...roomData, maxChildren: roomData.maxChildren + 1 })}
                        className="w-12 h-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center border-l border-slate-200 dark:border-slate-700"
                      >
                        <span className="material-symbols-outlined text-sm">add</span>
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </section>

            {/* Amenities Section */}
            <section className="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
              <div className="flex items-center gap-2 mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                <span className="material-symbols-outlined text-primary">grid_view</span>
                <h3 className="text-slate-900 dark:text-white text-xl font-bold">
                  {isEn ? 'Room Amenities & Features' : 'Vifaa na Sifa za Chumba'}
                </h3>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {allAmenities.map((amenity) => {
                  // Check if amenity exists in room data (handle both API format and local format)
                  const amenityKey = amenity.id;
                  const isChecked = roomData.amenities.some((a: string) => 
                    a.toLowerCase().includes(amenityKey) || 
                    a.toLowerCase() === amenityKey ||
                    amenityKey === 'wifi' && a.toLowerCase().includes('wifi') ||
                    amenityKey === 'ac' && (a.toLowerCase().includes('air') || a.toLowerCase().includes('conditioning'))
                  );
                  
                  return (
                    <label
                      key={amenity.id}
                      className="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                    >
                    <input
                      className="rounded border-slate-300 text-primary focus:ring-primary h-5 w-5"
                      type="checkbox"
                      checked={isChecked}
                      onChange={() => !isReadOnly && toggleAmenity(amenity.id)}
                      disabled={isReadOnly}
                    />
                      <span className="text-slate-700 dark:text-slate-300 text-sm">{amenity.label}</span>
                    </label>
                  );
                })}
              </div>
              {room?.amenities && room.amenities.length > 0 && (
                <div className="mt-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                  <p className="text-sm font-semibold mb-2">{isEn ? 'Current Amenities:' : 'Vifaa vya Sasa:'}</p>
                  <div className="flex flex-wrap gap-2">
                    {room.amenities.map((amenity, index) => (
                      <span key={index} className="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-semibold">
                        {amenity}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </section>
          </div>

          {/* Sidebar Preview */}
          <div className="lg:col-span-1">
            <div className="sticky top-8 space-y-4">
              <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-md overflow-hidden">
                <div className="h-56 bg-cover bg-center relative">
                  {photos[0] && (
                    <Image
                      src={photos[0]}
                      alt="Room Preview"
                      fill
                      className="object-cover"
                      unoptimized={photos[0].startsWith('http://127.0.0.1') || photos[0].startsWith('http://localhost')}
                    />
                  )}
                  <button className="absolute top-3 right-3 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors">
                    <span className="material-symbols-outlined text-sm">zoom_in</span>
                  </button>
                  <div className="absolute bottom-3 left-3 bg-primary px-3 py-1 rounded-full text-[10px] font-bold text-white uppercase tracking-wider">
                    {isEn ? 'Current Preview' : 'Onyesho la Sasa'}
                  </div>
                </div>
                <div className="p-6">
                  <h4 className="text-slate-900 dark:text-white text-xl font-bold mb-1">
                    {isEn ? 'Room Preview' : 'Onyesho la Chumba'}
                  </h4>
                  <p className="text-slate-500 dark:text-slate-400 text-sm mb-4">
                    {isEn ? 'Live summary of configuration' : 'Muhtasari wa moja kwa moja wa usanidi'}
                  </p>
                  <div className="grid grid-cols-2 gap-4 py-4 border-y border-slate-100 dark:border-slate-800 mb-6">
                    <div>
                      <p className="text-[10px] uppercase tracking-wider text-slate-400 font-bold">
                        {isEn ? 'Category' : 'Jamii'}
                      </p>
                      <p className="text-sm font-bold text-slate-800 dark:text-slate-200">
                        {roomTypes.find((t) => t.value === roomData.type)?.label || room?.type || 'N/A'}
                      </p>
                    </div>
                    <div>
                      <p className="text-[10px] uppercase tracking-wider text-slate-400 font-bold">
                        {isEn ? 'Nightly Rate' : 'Bei ya Usiku'}
                      </p>
                      <p className="text-sm font-bold text-primary">
                        TZS {room?.price ? room.price.toLocaleString() : roomData.price}
                      </p>
                    </div>
                    <div>
                      <p className="text-[10px] uppercase tracking-wider text-slate-400 font-bold">
                        {isEn ? 'Capacity' : 'Uwezo'}
                      </p>
                      <p className="text-sm font-bold text-slate-800 dark:text-slate-200">
                        {roomData.maxAdults} {isEn ? 'Adults' : 'Watu Wazima'}
                        {roomData.maxChildren > 0 && `, ${roomData.maxChildren} ${isEn ? 'Child' : 'Mtoto'}`}
                      </p>
                    </div>
                    <div>
                      <p className="text-[10px] uppercase tracking-wider text-slate-400 font-bold">
                        {isEn ? 'Status' : 'Hali'}
                      </p>
                      <div className="flex items-center gap-1.5 mt-0.5">
                        <div className={`size-2 rounded-full ${
                          roomData.status === 'available' ? 'bg-emerald-500' :
                          roomData.status === 'maintenance' ? 'bg-yellow-500' :
                          'bg-red-500'
                        }`}></div>
                        <p className={`text-xs font-bold uppercase ${
                          roomData.status === 'available' ? 'text-emerald-500' :
                          roomData.status === 'maintenance' ? 'text-yellow-500' :
                          'text-red-500'
                        }`}>
                          {roomData.status === 'available'
                            ? isEn
                              ? 'Available'
                              : 'Inapatikana'
                            : roomData.status === 'maintenance'
                            ? isEn
                              ? 'Maintenance'
                              : 'Matengenezo'
                            : isEn
                            ? 'Out of Order'
                            : 'Haifanyi Kazi'}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {!isReadOnly && (
                <div className="bg-blue-500/5 border border-blue-500/20 rounded-xl p-4 flex gap-4 items-start">
                  <span className="material-symbols-outlined text-blue-500">info</span>
                  <div>
                    <h5 className="text-sm font-bold text-slate-900 dark:text-white">
                      {isEn ? 'Admin Note' : 'Kumbuka la Msimamizi'}
                    </h5>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                      {isEn
                        ? 'Updates to room pricing and media will be reflected immediately on the public booking page.'
                        : 'Mabadiliko ya bei ya chumba na media yataonekana mara moja kwenye ukurasa wa umma wa kuomba.'}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}
