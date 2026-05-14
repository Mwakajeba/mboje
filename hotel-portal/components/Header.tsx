'use client';

import React from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';

const Header: React.FC = () => {
  const { isAuthenticated, user, logout } = useAuth();
  const { language, toggleLanguage } = useLanguage();
  const isEn = language === 'en';

  return (
    <header className="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 md:px-20 py-4 bg-white/80 dark:bg-background-dark/80 blur-bg border-b border-gray-200/50 dark:border-gray-800/50">
      <Link href="/" className="flex items-center gap-3">
        <div className="relative w-10 h-10">
          <Image 
            src="/logo.png" 
            alt="Hotel Logo" 
            fill
            className="object-contain"
            priority
          />
        </div>
      </Link>
      
      {!isAuthenticated ? (
        // Public Menu - Kwa wale wanaoomba ku book vyumba tu
        <nav className="hidden md:flex items-center gap-8">
          <Link href="/" className="text-sm font-semibold hover:text-primary transition-colors flex items-center gap-1">
            <span className="material-symbols-outlined text-base">home</span>
            {isEn ? 'Home' : 'Nyumbani'}
          </Link>
          <Link href="/about" className="text-sm font-semibold hover:text-primary transition-colors flex items-center gap-1">
            <span className="material-symbols-outlined text-base">info</span>
            {isEn ? 'About us' : 'Kuhusu Sisi'}
          </Link>
          <Link href="/contact" className="text-sm font-semibold hover:text-primary transition-colors flex items-center gap-1">
            <span className="material-symbols-outlined text-base">contact_support</span>
            {isEn ? 'Contact' : 'Mawasiliano'}
          </Link>
        </nav>
      ) : (
        // Authenticated Menu - Baada ya sign in
        <nav className="hidden md:flex items-center gap-8">
          <Link href="/dashboard" className="text-sm font-semibold hover:text-primary transition-colors flex items-center gap-1">
            <span className="material-symbols-outlined text-base">dashboard</span>
            {isEn ? 'Dashboard' : 'Dashibodi'}
          </Link>
          <Link href="/my-bookings" className="text-sm font-semibold hover:text-primary transition-colors flex items-center gap-1">
            <span className="material-symbols-outlined text-base">event</span>
            {isEn ? 'My bookings' : 'Maombi Yangu'}
          </Link>
          <Link href="/profile" className="text-sm font-semibold hover:text-primary transition-colors flex items-center gap-1">
            <span className="material-symbols-outlined text-base">person</span>
            {isEn ? 'Profile' : 'Profaili'}
          </Link>
        </nav>
      )}
      
      <div className="flex items-center gap-4">
        <button
          type="button"
          onClick={toggleLanguage}
          className="text-xs font-semibold px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 bg-white/80 dark:bg-background-dark/80 hover:border-primary transition-colors"
        >
          {isEn ? 'EN / SW' : 'SW / EN'}
        </button>

        {!isAuthenticated ? (
          <>
            <Link href="/login" className="hidden sm:block text-sm font-bold px-4 py-2 hover:text-primary transition-colors">
              {isEn ? 'Sign in' : 'Ingia'}
            </Link>
            <Link href="/signup" className="bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-lg hover:bg-primary/90 transition-all shadow-md">
              {isEn ? 'Sign up' : 'Jisajili'}
            </Link>
          </>
        ) : (
          <>
            <div className="hidden sm:flex items-center gap-2 text-sm">
              <span className="material-symbols-outlined text-base">person</span>
              <span className="font-medium">{user?.name}</span>
            </div>
            <button 
              onClick={logout}
              className="text-sm font-bold px-4 py-2 hover:text-primary transition-colors flex items-center gap-1"
            >
              <span className="material-symbols-outlined text-base">logout</span>
              {isEn ? 'Logout' : 'Toka'}
            </button>
          </>
        )}
      </div>
    </header>
  );
};

export default Header;
