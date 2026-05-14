'use client';

import React, { useState, useRef } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth, getRedirectUrl, clearRedirectUrl } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';

export default function LoginPage() {
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const { login, isAuthenticated } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';
  const router = useRouter();
  const redirectHandledRef = useRef(false);

  React.useEffect(() => {
    // Get redirect URL from query params or sessionStorage
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const redirect = params.get('redirect');
      if (redirect) {
        // Store redirect URL in sessionStorage
        sessionStorage.setItem('redirect_url', redirect);
      }
    }
  }, []);

  // If already logged in, redirect away from login page
  React.useEffect(() => {
    if (!isAuthenticated || redirectHandledRef.current) return;

    // Use a small delay to avoid race conditions with handleSubmit
    const timeoutId = setTimeout(() => {
      // Don't redirect if handleSubmit already handled it
      if (redirectHandledRef.current) return;
    
      // Check for booking data first (highest priority), then redirect URL
      let redirectUrl: string | null = null;
      
    const bookingData = sessionStorage.getItem('pending_booking');
    if (bookingData) {
      try {
        const booking = JSON.parse(bookingData);
          console.log('useEffect: Found booking data:', booking);
          // Use hashid if available, otherwise use numeric ID (matching buildBookingUrl logic)
          const roomIdentifier = booking.room_hashid || booking.room_id;
          redirectUrl = `/book/${roomIdentifier}?checkIn=${booking.checkIn}&checkOut=${booking.checkOut}&adults=${booking.adults}&children=${booking.children}`;
          console.log('useEffect: Redirecting to booking page:', redirectUrl);
        sessionStorage.removeItem('pending_booking');
      } catch (e) {
        console.error('Failed to parse booking data:', e);
      }
    }

      // Fallback to saved redirect URL if no booking data
      if (!redirectUrl) {
        redirectUrl = getRedirectUrl();
    if (redirectUrl) {
      clearRedirectUrl();
        }
      }

      if (redirectUrl) {
        redirectHandledRef.current = true;
      router.replace(redirectUrl);
    } else {
        redirectHandledRef.current = true;
      router.replace('/dashboard');
    }
    }, 150);

    return () => clearTimeout(timeoutId);
  }, [isAuthenticated, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);
    
    if (!phone || !password) {
      setError(isEn ? 'Please fill in all fields' : 'Tafadhali jaza nafasi zote');
      setIsLoading(false);
      return;
    }

    try {
      await login(phone, password);
      
      // Check for booking data immediately (before any delays)
      let redirectUrl: string | null = null;
      const bookingData = sessionStorage.getItem('pending_booking');
      
      if (bookingData) {
        try {
          const booking = JSON.parse(bookingData);
          console.log('Login handleSubmit: Found booking data:', booking);
          // Use hashid if available, otherwise use numeric ID (matching buildBookingUrl logic)
          const roomIdentifier = booking.room_hashid || booking.room_id;
          if (!roomIdentifier) {
            console.error('No room identifier found in booking data');
          } else {
            redirectUrl = `/book/${roomIdentifier}?checkIn=${booking.checkIn}&checkOut=${booking.checkOut}&adults=${booking.adults}&children=${booking.children}`;
            console.log('Login handleSubmit: Redirecting to booking page:', redirectUrl);
          sessionStorage.removeItem('pending_booking');
          }
        } catch (e) {
          console.error('Failed to parse booking data:', e);
        }
      } else {
        console.log('Login handleSubmit: No booking data found in sessionStorage');
      }

      // Fallback to saved redirect URL if no booking data
      if (!redirectUrl) {
        redirectUrl = getRedirectUrl();
      if (redirectUrl) {
          console.log('Login handleSubmit: Using redirect URL:', redirectUrl);
        clearRedirectUrl();
        }
      }

      // Mark that we've handled the redirect to prevent useEffect from also redirecting
      redirectHandledRef.current = true;
      
      // Small delay to ensure state is updated, then redirect
      setTimeout(() => {
        if (redirectUrl) {
          console.log('Login handleSubmit: Final redirect to:', redirectUrl);
        router.push(redirectUrl);
      } else {
          console.log('Login handleSubmit: No redirect URL, going to dashboard');
        router.push('/dashboard');
      }
      }, 50);
    } catch (err: any) {
      setError(err.message || (isEn ? 'Login failed. Please try again.' : 'Kuna tatizo la kuingia. Tafadhali jaribu tena.'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-lg mx-auto px-6 w-full">
          <div className="bg-white dark:bg-background-dark rounded-xl shadow-lg p-8">
            <h1 className="text-3xl font-black mb-2">
              {isEn ? 'Log In' : 'Ingia'}
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              {isEn
                ? 'Sign in to your account to book rooms'
                : 'Ingia kwenye akaunti yako kuomba vyumba'}
            </p>
            
            {error && (
              <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-lg mb-4">
                {error}
              </div>
            )}
            
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-bold mb-2">
                  {isEn ? 'Phone Number' : 'Nambari ya Simu'}
                </label>
                <input
                  type="tel"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                  placeholder={isEn ? '+255715XXXXXX' : '+255715XXXXXX'}
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-bold mb-2">
                  {isEn ? 'Password' : 'Nenosiri'}
                </label>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                  placeholder="••••••••"
                  required
                />
              </div>
              
              <button
                type="submit"
                disabled={isLoading}
                className="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/90 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {isLoading ? (
                  <>
                    <span className="material-symbols-outlined animate-spin">refresh</span>
                    <span>{isEn ? 'Logging in...' : 'Inaingia...'}</span>
                  </>
                ) : (
                  isEn ? 'Log In' : 'Ingia'
                )}
              </button>
            </form>
            
            <div className="mt-6 text-center">
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {isEn ? "Don't have an account? " : 'Huna akaunti? '}
                <Link href="/signup" className="text-primary font-bold hover:underline">
                  {isEn ? 'Sign up here' : 'Jisajili hapa'}
                </Link>
              </p>
            </div>
          </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}
