'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth, getRedirectUrl, clearRedirectUrl } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';

export default function SignupPage() {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const { signup } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';
  const router = useRouter();

  useEffect(() => {
    // Get redirect URL from query params
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const redirect = params.get('redirect');
      if (redirect) {
        // Store redirect URL in sessionStorage
        sessionStorage.setItem('redirect_url', redirect);
      }
    }
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);
    
    if (!firstName || !lastName || !phone || !password || !confirmPassword) {
      setError(isEn ? 'Please fill in all required fields' : 'Tafadhali jaza nafasi zote za lazima');
      setIsLoading(false);
      return;
    }

    if (password !== confirmPassword) {
      setError(isEn ? 'Passwords do not match' : 'Nenosiri hazifanani');
      setIsLoading(false);
      return;
    }

    if (password.length < 6) {
      setError(isEn ? 'Password must be at least 6 characters' : 'Nenosiri lazima liwe na angalau herufi 6');
      setIsLoading(false);
      return;
    }

    try {
      await signup(firstName, lastName, phone, email, password, confirmPassword);
      
      // Small delay to ensure state is updated, then redirect
      setTimeout(() => {
        // Check for booking data first (highest priority), then redirect URL
        let redirectUrl: string | null = null;
      
      const bookingData = sessionStorage.getItem('pending_booking');
      if (bookingData) {
        try {
          const booking = JSON.parse(bookingData);
            console.log('Found booking data:', booking);
            // Use hashid if available, otherwise use numeric ID (matching buildBookingUrl logic)
            const roomIdentifier = booking.room_hashid || booking.room_id;
            redirectUrl = `/book/${roomIdentifier}?checkIn=${booking.checkIn}&checkOut=${booking.checkOut}&adults=${booking.adults}&children=${booking.children}`;
            console.log('Redirecting to booking page:', redirectUrl);
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
        router.push(redirectUrl);
      } else {
        router.push('/dashboard');
      }
      }, 50);
    } catch (err: any) {
      setError(err.message || (isEn ? 'Registration failed. Please try again.' : 'Kuna tatizo la kujisajili. Tafadhali jaribu tena.'));
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
              {isEn ? 'Sign Up' : 'Jisajili'}
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              {isEn
                ? 'Create a new account to book rooms'
                : 'Unda akaunti mpya kuomba vyumba'}
            </p>
            
            {error && (
              <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-lg mb-4">
                {error}
              </div>
            )}
            
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-bold mb-2">
                    {isEn ? 'First Name' : 'Jina la Kwanza'} <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                    placeholder={isEn ? 'First name' : 'Jina la kwanza'}
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold mb-2">
                    {isEn ? 'Last Name' : 'Jina la Mwisho'} <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                    placeholder={isEn ? 'Last name' : 'Jina la mwisho'}
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-bold mb-2">
                  {isEn ? 'Phone Number' : 'Nambari ya Simu'} <span className="text-red-500">*</span>
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
                  {isEn ? 'Email Address (Optional)' : 'Barua Pepe (Hiari)'}
                </label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                  placeholder={isEn ? 'your@email.com (optional)' : 'jina@mfano.com (hiari)'}
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
                  minLength={6}
                />
              </div>

              <div>
                <label className="block text-sm font-bold mb-2">
                  {isEn ? 'Confirm Password' : 'Thibitisha Nenosiri'}
                </label>
                <input
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
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
                    <span>{isEn ? 'Signing up...' : 'Inajisajili...'}</span>
                  </>
                ) : (
                  isEn ? 'Sign Up' : 'Jisajili'
                )}
              </button>
            </form>
            
            <div className="mt-6 text-center">
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {isEn ? 'Already have an account? ' : 'Tayari una akaunti? '}
                <Link href="/login" className="text-primary font-bold hover:underline">
                  {isEn ? 'Log in here' : 'Ingia hapa'}
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
