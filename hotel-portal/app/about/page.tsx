'use client';

import React, { useState, useEffect } from 'react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useLanguage } from '@/contexts/LanguageContext';
import { branchesAPI, Branch } from '@/lib/api/branches';
import { messagesAPI } from '@/lib/api/messages';
import Swal from 'sweetalert2';

export default function AboutPage() {
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
        source: 'about_page',
      });

      await Swal.fire({
        title: isEn ? 'Message Sent!' : 'Ujumbe Umetumwa!',
        text: isEn ? 'Thank you for contacting us. We will get back to you soon.' : 'Asante kwa kuwasiliana nasi. Tutakujibu hivi karibuni.',
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
              {isEn ? 'About Us' : 'Kuhusu Sisi'}
            </h2>
            <p className="text-white/90 text-lg md:text-xl font-medium max-w-2xl mx-auto drop-shadow-md">
              {isEn
                ? 'Learn more about our mission to provide exceptional hospitality experiences.'
                : 'Jifunze zaidi kuhusu dhamira yetu ya kutoa uzoefu bora wa ukarimu.'}
            </p>
          </div>
        </div>

        {/* About Content & Contact Form */}
        <section className="relative z-20 -mt-20 max-w-7xl mx-auto w-full px-6 mb-20">
          <div className="bg-white dark:bg-background-dark rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2">
            {/* About Content Side */}
            <div className="p-8 md:p-12 border-r border-gray-100 dark:border-gray-800">
              <div className="mb-8">
                <h3 className="text-2xl font-bold mb-4">
                  {isEn ? 'Our Story' : 'Hadithi Yetu'}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                  {isEn
                    ? 'HotelStay is a modern hotel management system that enables customers to book rooms easily and quickly. We provide excellent accommodation services and ensure our customers get comfort and convenience at all times.'
                    : 'HotelStay ni mfumo wa kisasa wa usimamizi wa hoteli unaowezesha wateja kuomba vyumba kwa urahisi na kwa haraka. Tunatoa huduma bora za malazi na tunahakikisha wateja wetu wanapata starehe na faraja wakati wote.'}
                </p>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                  {isEn
                    ? 'Founded in 2020, HotelStay has grown to become one of the leading hotel booking platforms in the region. Our mission is to connect travelers with exceptional accommodations while providing hoteliers with powerful tools to manage their properties efficiently.'
                    : 'Iliyoanzishwa mwaka 2020, HotelStay imekua na kuwa moja ya jukwaa kuu la kuomba hoteli katika eneo hilo. Dhamira yetu ni kuunganisha wasafiri na malazi bora huku tukiwapa wamiliki wa hoteli zana zenye nguvu za kusimamia mali zao kwa ufanisi.'}
                </p>
                <h4 className="text-xl font-bold mb-3 mt-6">
                  {isEn ? 'Our Goals' : 'Malengo Yetu'}
                </h4>
                <ul className="space-y-3 text-gray-600 dark:text-gray-400 mb-6">
                  <li className="flex items-start gap-3">
                    <span className="material-symbols-outlined text-primary">check_circle</span>
                    <span>
                      {isEn
                        ? 'Provide excellent accommodation services'
                        : 'Kutoa huduma bora za malazi'}
                    </span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="material-symbols-outlined text-primary">check_circle</span>
                    <span>
                      {isEn
                        ? 'Simplify the room booking process'
                        : 'Kurahisisha mchakato wa kuomba vyumba'}
                    </span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="material-symbols-outlined text-primary">check_circle</span>
                    <span>
                      {isEn
                        ? 'Ensure customers get value for their money'
                        : 'Kuhakikisha wateja wanapata thamani ya pesa zao'}
                    </span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="material-symbols-outlined text-primary">check_circle</span>
                    <span>
                      {isEn
                        ? 'Support local hospitality businesses'
                        : 'Kuunga mkono biashara za ukarimu za ndani'}
                    </span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="material-symbols-outlined text-primary">check_circle</span>
                    <span>
                      {isEn
                        ? 'Promote sustainable tourism practices'
                        : 'Kukuza mazoea ya utalii endelevu'}
                    </span>
                  </li>
                </ul>
                <h4 className="text-xl font-bold mb-3 mt-6">
                  {isEn ? 'Why Choose Us' : 'Kwa Nini Utuchague'}
                </h4>
                <div className="grid grid-cols-1 gap-4">
                  <div className="flex items-start gap-3 p-4 bg-primary/5 rounded-lg">
                    <span className="material-symbols-outlined text-primary text-2xl">verified</span>
                    <div>
                      <h5 className="font-bold mb-1">
                        {isEn ? 'Verified Properties' : 'Mali Zilizothibitishwa'}
                      </h5>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        {isEn
                          ? 'All hotels and accommodations are verified for quality and safety standards.'
                          : 'Hotel zote na malazi zimehakikiwa kwa viwango vya ubora na usalama.'}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3 p-4 bg-primary/5 rounded-lg">
                    <span className="material-symbols-outlined text-primary text-2xl">support_agent</span>
                    <div>
                      <h5 className="font-bold mb-1">
                        {isEn ? '24/7 Customer Support' : 'Msaada wa Wateja 24/7'}
                      </h5>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        {isEn
                          ? 'Our dedicated support team is available around the clock to assist you.'
                          : 'Timu yetu ya msaada imejitolea inapatikana kila wakati kukusaidia.'}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3 p-4 bg-primary/5 rounded-lg">
                    <span className="material-symbols-outlined text-primary text-2xl">payments</span>
                    <div>
                      <h5 className="font-bold mb-1">
                        {isEn ? 'Best Price Guarantee' : 'Dhamana ya Bei Bora'}
                      </h5>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        {isEn
                          ? 'We guarantee the best prices or we\'ll match the difference.'
                          : 'Tunahakikisha bei bora au tutalinganisha tofauti.'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Contact Form Side */}
            <div className="p-8 md:p-12">
              <div className="mb-8">
                <h3 className="text-2xl font-bold mb-2">
                  {isEn ? 'Get in Touch' : 'Wasiliana Nasi'}
                </h3>
                <p className="text-gray-500 dark:text-gray-400">
                  {isEn
                    ? 'Have questions? Send us a message and we\'ll get back to you.'
                    : 'Una maswali? Tutume ujumbe na tutakujibu.'}
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
                    placeholder={isEn ? 'Inquiry' : 'Utafutaji'}
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
                    placeholder={isEn ? 'Tell us more...' : 'Tuambie zaidi...'}
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
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}
