'use client';

import React, { useState, useEffect } from 'react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useLanguage } from '@/contexts/LanguageContext';
import { branchesAPI, Branch } from '@/lib/api/branches';
import { messagesAPI } from '@/lib/api/messages';
import Swal from 'sweetalert2';

export default function ContactPage() {
  const { language } = useLanguage();
  const isEn = language === 'en';
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: '',
    message: '',
    branch_id: '',
  });
  const [branches, setBranches] = useState<Branch[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    branchesAPI.getBranches()
      .then((branchesList) => {
        setBranches(branchesList);
      })
      .catch((error) => {
        console.error('Failed to load branches:', error);
      });
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      await messagesAPI.sendMessage({
        name: formData.name,
        email: formData.email,
        subject: formData.subject,
        message: formData.message,
        branch_id: formData.branch_id ? Number(formData.branch_id) : undefined,
        source: 'contact_page',
      });

      await Swal.fire({
        title: isEn ? 'Message Sent!' : 'Ujumbe Umetumwa!',
        text: isEn ? 'Thank you for contacting us. We will get back to you within 2-4 hours during business days.' : 'Asante kwa kuwasiliana nasi. Tutakujibu ndani ya masaa 2-4 wakati wa siku za kazi.',
        icon: 'success',
        confirmButtonColor: '#2563eb',
      });

      // Reset form
      setFormData({
        name: '',
        email: '',
        subject: '',
        message: '',
        branch_id: '',
      });
    } catch (err: any) {
      await Swal.fire({
        title: isEn ? 'Error!' : 'Kosa!',
        text: err.message || (isEn ? 'Failed to send message' : 'Imeshindwa kutuma ujumbe'),
        icon: 'error',
        confirmButtonColor: '#dc2626',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

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
              {isEn ? 'Get in Touch' : 'Wasiliana Nasi'}
            </h2>
            <p className="text-white/90 text-lg md:text-xl font-medium max-w-2xl mx-auto drop-shadow-md">
              {isEn
                ? 'Our dedicated support team is here to assist you with your luxury stay.'
                : 'Timu yetu ya msaada imejitolea kukusaidia na malazi yako ya anasa.'}
            </p>
          </div>
        </div>

        {/* Contact Form Section */}
        <section className="relative z-20 -mt-20 max-w-7xl mx-auto w-full px-6 mb-20">
          <div className="bg-white dark:bg-background-dark rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2">
            {/* Form Side */}
            <div className="p-8 md:p-12 border-r border-gray-100 dark:border-gray-800">
              <div className="mb-8">
                <h3 className="text-2xl font-bold mb-2">
                  {isEn ? 'Send us a message' : 'Tutume ujumbe'}
                </h3>
                <p className="text-gray-500 dark:text-gray-400">
                  {isEn
                    ? 'We usually respond within 2-4 hours during business days.'
                    : 'Kwa kawaida tunajibu ndani ya masaa 2-4 wakati wa siku za kazi.'}
                </p>
              </div>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-widest text-gray-400">
                      {isEn ? 'Full Name' : 'Jina Kamili'}
                    </label>
                    <input
                      className="w-full h-12 bg-gray-50 dark:bg-gray-800 border-none rounded-lg px-4 text-sm focus:ring-2 focus:ring-primary/40 transition-all"
                      placeholder={isEn ? 'John Doe' : 'Jina lako'}
                      type="text"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-widest text-gray-400">
                      {isEn ? 'Email Address' : 'Anwani ya Barua Pepe'}
                    </label>
                    <input
                      className="w-full h-12 bg-gray-50 dark:bg-gray-800 border-none rounded-lg px-4 text-sm focus:ring-2 focus:ring-primary/40 transition-all"
                      placeholder={isEn ? 'john@example.com' : 'jina@mfano.com'}
                      type="email"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-gray-400">
                    {isEn ? 'Branch' : 'Tawi'} <span className="text-red-500">*</span>
                  </label>
                  <select
                    className="w-full h-12 bg-gray-50 dark:bg-gray-800 border-none rounded-lg px-4 text-sm focus:ring-2 focus:ring-primary/40 transition-all appearance-none"
                    value={formData.branch_id}
                    onChange={(e) => setFormData({ ...formData, branch_id: e.target.value })}
                    required
                  >
                    <option value="">{isEn ? 'Select Branch' : 'Chagua Tawi'}</option>
                    {branches.map((branch) => (
                      <option key={branch.id} value={branch.id}>
                        {branch.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-gray-400">
                    {isEn ? 'Subject' : 'Somo'}
                  </label>
                  <input
                    className="w-full h-12 bg-gray-50 dark:bg-gray-800 border-none rounded-lg px-4 text-sm focus:ring-2 focus:ring-primary/40 transition-all"
                    placeholder={isEn ? 'Booking Inquiry' : 'Utafutaji wa Kuomba'}
                    type="text"
                    value={formData.subject}
                    onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-gray-400">
                    {isEn ? 'Message' : 'Ujumbe'}
                  </label>
                  <textarea
                    className="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-lg p-4 text-sm focus:ring-2 focus:ring-primary/40 transition-all"
                    placeholder={isEn ? 'Tell us more about your request...' : 'Tuambie zaidi kuhusu ombi lako...'}
                    rows={5}
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    required
                  />
                </div>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full py-4 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg flex items-center justify-center gap-2 group disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isSubmitting ? (
                    <>
                      <span className="material-symbols-outlined animate-spin">refresh</span>
                      <span>{isEn ? 'Sending...' : 'Inatumia...'}</span>
                    </>
                  ) : (
                    <>
                      <span>{isEn ? 'Send Message' : 'Tuma Ujumbe'}</span>
                      <span className="material-symbols-outlined group-hover:translate-x-1 transition-transform">send</span>
                    </>
                  )}
                </button>
              </form>
            </div>

            {/* Info Side */}
            <div className="flex flex-col">
              <div className="h-80 relative bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div
                  className="absolute inset-0 bg-cover bg-center grayscale opacity-60"
                  style={{
                    backgroundImage: `url('https://lh3.googleusercontent.com/aida-public/AB6AXuDQPPhUsa7ZqT5XGmd_H2tWbfuCuxp3cf1k95nPZlsdVGkri0dfm9WpLSQcGXi7nLujWgTXOlLhhhpoZkVj9-Y7Gf-xJ0sn-T3o7IHqeXqRFJ2GYBlPOFxv_0EfYKzFd2mGH2CQ06qr8_abDWIKgu2J0kXZ9mqkb9Zq2T6wDmmVHPMG3KFkPwS5aLVj5bCBiElDQQkDVwJ6SnJXzZDHqiEC0CjS2ll8vBx8gXp-NqPCzmfz3Ov0JvkZfoLFzJEq3xQoXrJrKPXaB54')`,
                  }}
                />
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="bg-primary text-white p-4 rounded-full shadow-2xl animate-bounce">
                    <span className="material-symbols-outlined text-3xl">location_on</span>
                  </div>
                </div>
                <div className="absolute bottom-4 left-4 bg-white/90 dark:bg-background-dark/90 backdrop-blur p-3 rounded-lg text-xs font-bold shadow-sm">
                  {isEn ? 'Open in Google Maps' : 'Fungua kwenye Google Maps'}
                </div>
              </div>
              <div className="p-8 md:p-12 flex-1 space-y-8 bg-gray-50/50 dark:bg-gray-800/30">
                <div>
                  <h4 className="text-lg font-bold mb-4">
                    {isEn ? 'Contact Information' : 'Taarifa za Mawasiliano'}
                  </h4>
                  <div className="space-y-4 max-h-[600px] overflow-y-auto">
                    {branches.length > 0 ? (
                      branches.map((branch) => (
                        <div key={branch.id} className="flex items-start gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                          <div className="bg-primary/10 p-2 rounded-lg text-primary flex-shrink-0">
                            <span className="material-symbols-outlined">business</span>
                          </div>
                          <div className="flex-1">
                            <p className="text-sm font-bold mb-2">{branch.name}</p>
                            {branch.address && (
                              <div className="flex items-start gap-2 mb-2">
                                <span className="material-symbols-outlined text-xs text-gray-400 mt-0.5">location_on</span>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{branch.address}</p>
                              </div>
                            )}
                            {branch.phone && (
                              <div className="flex items-center gap-2 mb-1">
                                <span className="material-symbols-outlined text-xs text-gray-400">call</span>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{branch.phone}</p>
                              </div>
                            )}
                            {branch.email && (
                              <div className="flex items-center gap-2">
                                <span className="material-symbols-outlined text-xs text-gray-400">mail</span>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{branch.email}</p>
                              </div>
                            )}
                          </div>
                        </div>
                      ))
                    ) : (
                      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                        <span className="material-symbols-outlined text-4xl mb-2">business</span>
                        <p>{isEn ? 'Loading branches...' : 'Inapakia matawi...'}</p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* FAQ Section */}
        <section className="max-w-4xl mx-auto px-6 py-20 w-full">
          <div className="text-center mb-12">
            <span className="text-primary font-bold text-sm tracking-widest uppercase">
              {isEn ? 'Self-Service' : 'Huduma ya Kibinafsi'}
            </span>
            <h3 className="text-3xl font-black mt-2">
              {isEn ? 'Frequently Asked Questions' : 'Maswali Yanayoulizwa Mara kwa Mara'}
            </h3>
          </div>
          <div className="space-y-4">
            <div className="bg-white dark:bg-background-dark rounded-xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
              <div className="flex items-center justify-between cursor-pointer group">
                <h5 className="font-bold text-gray-800 dark:text-white">
                  {isEn
                    ? 'How do I modify or cancel my reservation?'
                    : 'Ninawezaje kubadilisha au kughairi hifadhi yangu?'}
                </h5>
                <span className="material-symbols-outlined text-gray-400 group-hover:text-primary">expand_more</span>
              </div>
              <div className="mt-4 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                {isEn
                  ? "You can modify or cancel your booking through the 'My Trips' section of your profile. Please check the cancellation policy of your specific booking as some rates may be non-refundable."
                  : 'Unaweza kubadilisha au kughairi kuomba kwako kupitia sehemu ya "Safari Zangu" ya profaili yako. Tafadhali angalia sera ya kughairi ya kuomba kwako maalum kwani baadhi ya bei zinaweza kuwa hazirudishwi.'}
              </div>
            </div>
            <div className="bg-white dark:bg-background-dark rounded-xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
              <div className="flex items-center justify-between cursor-pointer group">
                <h5 className="font-bold text-gray-800 dark:text-white">
                  {isEn
                    ? 'What are the standard check-in and check-out times?'
                    : 'Ni muda gani wa kawaida wa kuingia na kutoka?'}
                </h5>
                <span className="material-symbols-outlined text-gray-400 group-hover:text-primary">expand_more</span>
              </div>
              <div className="mt-4 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                {isEn
                  ? 'Standard check-in is at 3:00 PM and check-out is at 11:00 AM. Early check-in or late check-out can be requested but is subject to availability.'
                  : 'Kuingia kwa kawaida ni saa 3:00 PM na kutoka ni saa 11:00 AM. Kuingia mapema au kutoka baadaye kunaweza kuombwa lakini inategemea upatikanaji.'}
              </div>
            </div>
            <div className="bg-white dark:bg-background-dark rounded-xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
              <div className="flex items-center justify-between cursor-pointer group">
                <h5 className="font-bold text-gray-800 dark:text-white">
                  {isEn ? 'Do you offer airport shuttle services?' : 'Je, unatoa huduma za basi za uwanja wa ndege?'}
                </h5>
                <span className="material-symbols-outlined text-gray-400 group-hover:text-primary">expand_more</span>
              </div>
              <div className="mt-4 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                {isEn
                  ? 'Many of our luxury properties offer complimentary shuttle services. Please contact the specific hotel concierge after booking to arrange your transfer.'
                  : 'Mali nyingi za anasa zetu hutoa huduma za basi za bure. Tafadhali wasiliana na msimamizi wa hoteli maalum baada ya kuomba ili kupanga uhamisho wako.'}
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}
