'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useAuth } from '@/contexts/AuthContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { authAPI } from '@/lib/api/auth';
import Swal from 'sweetalert2';

export default function ProfilePage() {
  const { user, isAuthenticated, refreshUser } = useAuth();
  const { language } = useLanguage();
  const isEn = language === 'en';
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  });

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/login');
    } else if (user) {
      setFormData({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        phone: user.phone || '',
        password: '',
        password_confirmation: '',
      });
    }
  }, [isAuthenticated, user, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const updateData: any = {
        first_name: formData.first_name,
        last_name: formData.last_name,
      };

      if (formData.email && formData.email !== user?.email) {
        updateData.email = formData.email;
      }

      if (formData.phone && formData.phone !== user?.phone) {
        updateData.phone = formData.phone;
      }

      if (formData.password) {
        if (formData.password.length < 6) {
          throw new Error(isEn ? 'Password must be at least 6 characters' : 'Nenosiri lazima liwe na angalau herufi 6');
        }
        if (formData.password !== formData.password_confirmation) {
          throw new Error(isEn ? 'Passwords do not match' : 'Nenosiri hazifanani');
        }
        updateData.password = formData.password;
        updateData.password_confirmation = formData.password_confirmation;
      }

      await authAPI.updateProfile(updateData);
      
      // Refresh user data
      await refreshUser();

      await Swal.fire({
        title: isEn ? 'Success!' : 'Mafanikio!',
        text: isEn ? 'Profile updated successfully' : 'Profaili imesasishwa kwa mafanikio',
        icon: 'success',
        confirmButtonColor: '#2563eb',
      });
    } catch (err: any) {
      await Swal.fire({
        title: isEn ? 'Error!' : 'Kosa!',
        text: err.message || (isEn ? 'Failed to update profile' : 'Imeshindwa kusasisha profaili'),
        icon: 'error',
        confirmButtonColor: '#dc2626',
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (!isAuthenticated || !user) {
    return null;
  }

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-2xl mx-auto px-6 w-full">
          <div className="mb-8">
            <h1 className="text-4xl font-black mb-2">
              {isEn ? 'My Profile' : 'Profaili Yangu'}
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              {isEn ? 'Update your account information' : 'Sasisha taarifa za akaunti yako'}
            </p>
          </div>

          <div className="bg-white dark:bg-background-dark rounded-xl shadow-lg p-8">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-bold mb-2">
                    {isEn ? 'First Name' : 'Jina la Kwanza'} <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.first_name}
                    onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                    className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-bold mb-2">
                    {isEn ? 'Last Name' : 'Jina la Mwisho'} <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.last_name}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                    className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-bold mb-2">
                  {isEn ? 'Email Address' : 'Anwani ya Barua Pepe'}
                </label>
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                />
              </div>

              <div>
                <label className="block text-sm font-bold mb-2">
                  {isEn ? 'Phone Number' : 'Nambari ya Simu'} <span className="text-red-500">*</span>
                </label>
                <input
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  placeholder="+255 XXX XXX XXX"
                  className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                  required
                />
              </div>

              <div className="pt-4 border-t border-gray-200 dark:border-gray-800">
                <h3 className="text-lg font-bold mb-4">
                  {isEn ? 'Change Password' : 'Badilisha Nenosiri'}
                </h3>
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                  {isEn ? 'Leave blank if you don\'t want to change your password' : 'Acha wazi ikiwa hutaki kubadilisha nenosiri lako'}
                </p>
                
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-bold mb-2">
                      {isEn ? 'New Password' : 'Nenosiri Jipya'}
                    </label>
                    <input
                      type="password"
                      value={formData.password}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                      className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                      minLength={6}
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-bold mb-2">
                      {isEn ? 'Confirm New Password' : 'Thibitisha Nenosiri Jipya'}
                    </label>
                    <input
                      type="password"
                      value={formData.password_confirmation}
                      onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                      className="w-full h-12 bg-background-light dark:bg-gray-800 border-none rounded-lg px-4 text-sm font-medium focus:ring-2 focus:ring-primary/40 transition-all"
                      minLength={6}
                    />
                  </div>
                </div>
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/90 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {isLoading ? (
                  <>
                    <span className="material-symbols-outlined animate-spin">refresh</span>
                    <span>{isEn ? 'Updating...' : 'Inasasisha...'}</span>
                  </>
                ) : (
                  <>
                    <span className="material-symbols-outlined">save</span>
                    <span>{isEn ? 'Save Changes' : 'Hifadhi Mabadiliko'}</span>
                  </>
                )}
              </button>
            </form>
          </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}
