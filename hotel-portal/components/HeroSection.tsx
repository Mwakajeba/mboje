'use client';

import React from 'react';
import { useLanguage } from '@/contexts/LanguageContext';

const HeroSection: React.FC = () => {
  const { language } = useLanguage();
  const isEn = language === 'en';

  return (
    <div className="relative w-full h-[600px] md:h-[700px] flex flex-col items-center justify-center px-4 overflow-hidden">
      {/* Architectural Blurred Background */}
      <div 
        className="absolute inset-0 bg-center bg-no-repeat" 
        style={{
          backgroundImage: `linear-gradient(to bottom, rgba(16, 28, 34, 0.6), rgba(16, 28, 34, 0.4)), url('/Hotel Booking-rafiki.png')`,
          filter: 'grayscale(20%)',
          backgroundSize: '100% auto',
          backgroundPosition: 'center center'
        }}
        aria-label="Hotel booking illustration background"
      ></div>
      
      {/* Hero Content */}
      <div className="relative z-10 text-center mb-10">
        <h2 className="text-white text-4xl md:text-6xl font-black mb-4 drop-shadow-lg">
          {isEn ? 'Find your perfect stay' : 'Tafuta malazi yako kamili'}
        </h2>
        <p className="text-white/90 text-lg md:text-xl font-medium max-w-2xl mx-auto drop-shadow-md">
          {isEn
            ? "Experience luxury and comfort in the heart of the world's most vibrant cities."
            : 'Furahia starehe na faraja katika miji yenye haiba duniani.'}
        </p>
      </div>
      
      {/* Floating Search Card */}
      <div className="relative z-20 w-full max-w-6xl px-4 md:px-0">
        <div className="bg-white dark:bg-background-dark rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] p-6 md:p-8 flex flex-col gap-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            {/* Check-in / Check-out */}
            <div className="col-span-1 md:col-span-1 space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 ml-1">
                <span className="material-symbols-outlined text-primary scale-90">calendar_month</span>
                {isEn ? 'Dates' : 'Tarehe'}
              </label>
              <div className="relative">
                <input 
                  className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all placeholder:text-gray-400" 
                  placeholder={isEn ? 'Check-in — Check-out' : 'Kuingia — Kutoka'} 
                  type="text"
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
                <select className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 appearance-none transition-all pr-10">
                  <option value="2">{isEn ? '2 Adults, 0 Children' : 'Watu wazima 2, Watoto 0'}</option>
                  <option value="1">{isEn ? '1 Adult, 0 Children' : 'Mtu mzima 1, Watoto 0'}</option>
                  <option value="3">{isEn ? '2 Adults, 1 Child' : 'Watu wazima 2, Mtoto 1'}</option>
                  <option value="4">{isEn ? '2 Adults, 2 Children' : 'Watu wazima 2, Watoto 2'}</option>
                </select>
                <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <span className="material-symbols-outlined">expand_more</span>
                </div>
              </div>
            </div>
            
            {/* Promo Code */}
            <div className="col-span-1 md:col-span-1 space-y-2">
              <label className="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 ml-1">
                <span className="material-symbols-outlined text-primary scale-90">sell</span>
                {isEn ? 'Promo Code' : 'Kodi ya ofa'}
              </label>
              <div className="relative">
                <input 
                  className="w-full h-14 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all placeholder:text-gray-400" 
                  placeholder={isEn ? 'Optional' : 'Hiari'} 
                  type="text"
                />
              </div>
            </div>
            
            {/* CTA Button */}
            <div className="col-span-1">
              <a
                href="/rooms"
                className="w-full h-14 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg flex items-center justify-center gap-2 group"
              >
                <span>{isEn ? 'View rooms' : 'Angalia Vyumba'}</span>
                <span className="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
              </a>
            </div>
          </div>
          
          {/* Secondary Links/Info */}
          <div className="flex flex-wrap items-center gap-6 pt-2 border-t border-gray-100 dark:border-gray-800">
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <span className="material-symbols-outlined text-green-500 text-sm">check_circle</span>
              Best price guaranteed
            </div>
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <span className="material-symbols-outlined text-green-500 text-sm">check_circle</span>
              Free Cancellation
            </div>
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <span className="material-symbols-outlined text-green-500 text-sm">check_circle</span>
              No booking fees
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default HeroSection;
