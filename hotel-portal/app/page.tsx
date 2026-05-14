'use client';

import Header from '@/components/Header';
import DateRangeSearch from '@/components/DateRangeSearch';
import FeaturedDestinations from '@/components/FeaturedDestinations';
import Footer from '@/components/Footer';
import Link from 'next/link';
import { useLanguage } from '@/contexts/LanguageContext';

export default function Home() {
  const { language } = useLanguage();
  const isEn = language === 'en';

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-16">
        {/* Hero Section with Background */}
        <section
          id="room-search"
          className="relative w-full h-[500px] md:h-[600px] flex flex-col items-center justify-center px-4 overflow-hidden"
        >
          {/* Background Image */}
          <div
            className="absolute inset-0 bg-center bg-no-repeat"
            style={{
              backgroundImage: `linear-gradient(to bottom, rgba(16, 28, 34, 0.6), rgba(16, 28, 34, 0.4)), url('/Hotel Booking-rafiki.png')`,
              filter: 'grayscale(20%)',
              backgroundSize: '100% auto',
              backgroundPosition: 'center center',
            }}
            aria-label="Hotel booking illustration background"
          />

          {/* Hero Content */}
          <div className="relative z-10 text-center mb-8">
            <h2 className="text-white text-4xl md:text-6xl font-black mb-4 drop-shadow-lg">
              {isEn ? 'Find your perfect stay' : 'Tafuta malazi yako kamili'}
            </h2>
            <p className="text-white/90 text-lg md:text-xl font-medium max-w-2xl mx-auto drop-shadow-md">
              {isEn
                ? "Experience luxury and comfort in the heart of the world's most vibrant cities."
                : 'Furahia starehe na faraja katika miji yenye haiba duniani.'}
            </p>
          </div>

          {/* Date Range Search */}
          <div className="relative z-20 w-full">
            <DateRangeSearch />
          </div>
        </section>

        <FeaturedDestinations />

        {/* Call to Action Section */}
        <section className="max-w-7xl mx-auto px-6 py-20 w-full">
          <div className="bg-primary/10 rounded-xl p-12 text-center">
            <h2 className="text-3xl font-black mb-4">
              {isEn ? 'Ready to Book?' : 'Tayari Kukaribishwa?'}
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
              {isEn
                ? 'View all available rooms and book your perfect stay'
                : 'Angalia vyumba vyote vinavyopatikana na omba chumba chako cha kukukaribisha'}
            </p>
            <Link
              href="#room-search"
              className="inline-flex items-center gap-2 bg-primary text-white font-bold px-8 py-4 rounded-lg hover:bg-primary/90 transition-all shadow-lg"
            >
              <span className="material-symbols-outlined">hotel</span>
              <span>{isEn ? 'View All Rooms' : 'Angalia Vyumba'}</span>
            </Link>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}
