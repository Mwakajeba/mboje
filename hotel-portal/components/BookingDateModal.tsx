'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/contexts/LanguageContext';

interface BookingDateModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const BookingDateModal: React.FC<BookingDateModalProps> = ({ isOpen, onClose }) => {
  const { language } = useLanguage();
  const router = useRouter();
  const isEn = language === 'en';

  const [checkIn, setCheckIn] = useState('');
  const [checkOut, setCheckOut] = useState('');
  const [adults, setAdults] = useState('2');
  const [children, setChildren] = useState('0');

  React.useEffect(() => {
    if (isOpen) {
      const today = new Date();
      const threeDaysLater = new Date();
      threeDaysLater.setDate(today.getDate() + 3);

      const formatDate = (date: Date) => {
        return date.toISOString().split('T')[0];
      };

      setCheckIn(formatDate(today));
      setCheckOut(formatDate(threeDaysLater));
    }
  }, [isOpen]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (checkIn && checkOut) {
      const params = new URLSearchParams({
        checkIn,
        checkOut,
        adults,
        children,
      });
      router.push(`/rooms?${params.toString()}`);
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div className="bg-white dark:bg-slate-900 rounded-xl shadow-2xl p-6 md:p-8 max-w-2xl w-full mx-4">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-black text-[#0e141b] dark:text-white">
            {isEn ? 'Search Available Rooms' : 'Tafuta Vyumba Vinavyopatikana'}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          >
            <span className="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>

        <form onSubmit={handleSearch} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                <span className="material-symbols-outlined text-primary text-sm">calendar_month</span>
                {isEn ? 'Check-in' : 'Tarehe ya Kuingia'}
              </label>
              <input
                type="date"
                value={checkIn}
                onChange={(e) => setCheckIn(e.target.value)}
                min={new Date().toISOString().split('T')[0]}
                className="w-full h-12 bg-background-light dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary transition-all"
                required
              />
            </div>

            <div className="space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                <span className="material-symbols-outlined text-primary text-sm">event</span>
                {isEn ? 'Check-out' : 'Tarehe ya Kutoka'}
              </label>
              <input
                type="date"
                value={checkOut}
                onChange={(e) => setCheckOut(e.target.value)}
                min={checkIn || new Date().toISOString().split('T')[0]}
                className="w-full h-12 bg-background-light dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary transition-all"
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
              <span className="material-symbols-outlined text-primary text-sm">group</span>
              {isEn ? 'Guests' : 'Wageni'}
            </label>
            <select
              value={`${adults}-${children}`}
              onChange={(e) => {
                const [a, c] = e.target.value.split('-');
                setAdults(a);
                setChildren(c);
              }}
              className="w-full h-12 bg-background-light dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary appearance-none transition-all"
            >
              <option value="1-0">{isEn ? '1 Adult, 0 Children' : 'Mtu mzima 1, Watoto 0'}</option>
              <option value="2-0">{isEn ? '2 Adults, 0 Children' : 'Watu wazima 2, Watoto 0'}</option>
              <option value="2-1">{isEn ? '2 Adults, 1 Child' : 'Watu wazima 2, Mtoto 1'}</option>
              <option value="2-2">{isEn ? '2 Adults, 2 Children' : 'Watu wazima 2, Watoto 2'}</option>
              <option value="3-0">{isEn ? '3 Adults, 0 Children' : 'Watu wazima 3, Watoto 0'}</option>
              <option value="4-0">{isEn ? '4 Adults, 0 Children' : 'Watu wazima 4, Watoto 0'}</option>
            </select>
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 h-12 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
            >
              {isEn ? 'Cancel' : 'Ghairi'}
            </button>
            <button
              type="submit"
              className="flex-1 h-12 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center gap-2"
            >
              <span>{isEn ? 'Search Rooms' : 'Tafuta Vyumba'}</span>
              <span className="material-symbols-outlined text-sm">search</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default BookingDateModal;
