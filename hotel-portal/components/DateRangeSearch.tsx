'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/contexts/LanguageContext';

import { branchesAPI, Branch } from '@/lib/api/branches';

const DateRangeSearch: React.FC = () => {
  const { language } = useLanguage();
  const router = useRouter();
  const isEn = language === 'en';

  const [checkIn, setCheckIn] = useState('');
  const [checkOut, setCheckOut] = useState('');
  const [adults, setAdults] = useState('2');
  const [children, setChildren] = useState('0');
  const [promoCode, setPromoCode] = useState('');
  const [branches, setBranches] = useState<Branch[]>([]);
  const [selectedBranchId, setSelectedBranchId] = useState<number | ''>('');

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (checkIn && checkOut && selectedBranchId) {
      const params = new URLSearchParams({
        checkIn,
        checkOut,
        adults,
        children,
        branchId: selectedBranchId.toString(),
      });
      if (promoCode) {
        params.append('promo', promoCode);
      }
      router.push(`/rooms?${params.toString()}`);
    }
  };

  // Set default dates (today and 3 days later) and load branches
  React.useEffect(() => {
    const today = new Date();
    const threeDaysLater = new Date();
    threeDaysLater.setDate(today.getDate() + 3);

    const formatDate = (date: Date) => {
      return date.toISOString().split('T')[0];
    };

    setCheckIn(formatDate(today));
    setCheckOut(formatDate(threeDaysLater));

    // Load branches
    branchesAPI.getBranches()
      .then((branchesList) => {
        setBranches(branchesList);
        // Do not auto-select first branch - user must select manually
      })
      .catch((error) => {
        console.error('Failed to load branches:', error);
      });
  }, []);

  return (
    <div className="w-full max-w-6xl mx-auto px-4">
      <div className="bg-white dark:bg-background-dark rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] p-6 md:p-8">
        <form onSubmit={handleSearch} className="flex flex-col gap-6">
          <div className="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
            {/* Branch Selection */}
            <div className="col-span-1 md:col-span-1 space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 ml-1">
                <span className="material-symbols-outlined text-primary scale-90">business</span>
                {isEn ? 'Branch' : 'Tawi'} <span className="text-red-500">*</span>
              </label>
              <div className="relative">
                <select
                  value={selectedBranchId}
                  onChange={(e) => setSelectedBranchId(Number(e.target.value))}
                  className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 appearance-none transition-all pr-10"
                  required
                >
                  <option value="">{isEn ? 'Select Branch' : 'Chagua Tawi'}</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name}
                    </option>
                  ))}
                </select>
                <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <span className="material-symbols-outlined">expand_more</span>
                </div>
              </div>
            </div>

            {/* Check-in Date */}
            <div className="col-span-1 md:col-span-1 space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 ml-1">
                <span className="material-symbols-outlined text-primary scale-90">calendar_month</span>
                {isEn ? 'Check-in' : 'Tarehe ya Kuingia'}
              </label>
              <div className="relative">
                <input
                  type="date"
                  value={checkIn}
                  onChange={(e) => setCheckIn(e.target.value)}
                  min={new Date().toISOString().split('T')[0]}
                  className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                  required
                />
              </div>
            </div>

            {/* Check-out Date */}
            <div className="col-span-1 md:col-span-1 space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 ml-1">
                <span className="material-symbols-outlined text-primary scale-90">event</span>
                {isEn ? 'Check-out' : 'Tarehe ya Kutoka'}
              </label>
              <div className="relative">
                <input
                  type="date"
                  value={checkOut}
                  onChange={(e) => setCheckOut(e.target.value)}
                  min={checkIn || new Date().toISOString().split('T')[0]}
                  className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                  required
                />
              </div>
            </div>

            {/* Guests */}
            <div className="col-span-1 md:col-span-1 space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 ml-1">
                <span className="material-symbols-outlined text-primary scale-90">group</span>
                {isEn ? 'Guests' : 'Wageni'}
              </label>
              <div className="relative">
                <select
                  value={`${adults}-${children}`}
                  onChange={(e) => {
                    const [a, c] = e.target.value.split('-');
                    setAdults(a);
                    setChildren(c);
                  }}
                  className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 appearance-none transition-all pr-10"
                >
                  <option value="1-0">{isEn ? '1 Adult, 0 Children' : 'Mtu mzima 1, Watoto 0'}</option>
                  <option value="2-0">{isEn ? '2 Adults, 0 Children' : 'Watu wazima 2, Watoto 0'}</option>
                  <option value="2-1">{isEn ? '2 Adults, 1 Child' : 'Watu wazima 2, Mtoto 1'}</option>
                  <option value="2-2">{isEn ? '2 Adults, 2 Children' : 'Watu wazima 2, Watoto 2'}</option>
                  <option value="3-0">{isEn ? '3 Adults, 0 Children' : 'Watu wazima 3, Watoto 0'}</option>
                  <option value="4-0">{isEn ? '4 Adults, 0 Children' : 'Watu wazima 4, Watoto 0'}</option>
                </select>
                <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <span className="material-symbols-outlined">expand_more</span>
                </div>
              </div>
            </div>

            {/* Search Button */}
            <div className="col-span-1">
              <button
                type="submit"
                className="w-full h-14 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg flex items-center justify-center gap-2 group"
              >
                <span>{isEn ? 'Search Rooms' : 'Tafuta Vyumba'}</span>
                <span className="material-symbols-outlined group-hover:translate-x-1 transition-transform">search</span>
              </button>
            </div>
          </div>

          {/* Promo Code (Optional) */}
          <div className="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
            <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
              <span className="material-symbols-outlined text-primary scale-90">sell</span>
              {isEn ? 'Promo Code (Optional)' : 'Kodi ya Ofa (Hiari)'}
            </label>
            <input
              type="text"
              value={promoCode}
              onChange={(e) => setPromoCode(e.target.value)}
              placeholder={isEn ? 'Enter promo code' : 'Ingiza kodi ya ofa'}
              className="flex-1 h-10 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all placeholder:text-gray-400"
            />
          </div>

          {/* Benefits */}
          <div className="flex flex-wrap items-center gap-6 pt-2 border-t border-gray-100 dark:border-gray-800">
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <span className="material-symbols-outlined text-green-500 text-sm">check_circle</span>
              {isEn ? 'Best price guaranteed' : 'Bei bora imethibitishwa'}
            </div>
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <span className="material-symbols-outlined text-green-500 text-sm">check_circle</span>
              {isEn ? 'Free Cancellation' : 'Kughairi Bure'}
            </div>
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <span className="material-symbols-outlined text-green-500 text-sm">check_circle</span>
              {isEn ? 'No booking fees' : 'Hakuna ada za kuomba'}
            </div>
          </div>
        </form>
      </div>
    </div>
  );
};

export default DateRangeSearch;
