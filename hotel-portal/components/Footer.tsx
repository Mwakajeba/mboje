'use client';

import React, { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { useLanguage } from '@/contexts/LanguageContext';

const Footer: React.FC = () => {
  const { language } = useLanguage();
  const isEn = language === 'en';
  const [email, setEmail] = useState('');

  const handleNewsletterSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle newsletter subscription
    console.log('Newsletter subscription:', email);
    setEmail('');
  };

  return (
    <footer className="bg-white dark:bg-background-dark border-t border-gray-200 dark:border-gray-800 py-12 px-6">
      <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
        <div className="space-y-4">
          <div className="flex items-center gap-2">
            <div className="relative w-8 h-8">
              <Image 
                src="/logo.png" 
                alt="Hotel Logo" 
                fill
                className="object-contain"
              />
            </div>
            <span className="text-lg font-bold">HotelStay</span>
          </div>
          <p className="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
            {isEn
              ? 'Redefining the way you travel. Find luxury stays and unforgettable experiences anywhere in the world.'
              : 'Kubadilisha njia unavyosafiri. Tafuta malazi ya anasa na uzoefu usoweza kusahau popote duniani.'}
          </p>
        </div>
        <div className="space-y-4">
          <h5 className="font-bold text-sm uppercase tracking-widest">
            {isEn ? 'Support' : 'Msaada'}
          </h5>
          <ul className="space-y-2 text-sm text-gray-500 dark:text-gray-400">
            <li>
              <Link href="/contact" className="hover:text-primary transition-colors">
                {isEn ? 'Help Center' : 'Kituo cha Msaada'}
              </Link>
            </li>
            <li>
              <Link href="/contact" className="hover:text-primary transition-colors">
                {isEn ? 'Safety information' : 'Taarifa za Usalama'}
              </Link>
            </li>
            <li>
              <Link href="/contact" className="hover:text-primary transition-colors">
                {isEn ? 'Cancellation options' : 'Chaguzi za Kughairi'}
              </Link>
            </li>
          </ul>
        </div>
        <div className="space-y-4">
          <h5 className="font-bold text-sm uppercase tracking-widest">
            {isEn ? 'Company' : 'Kampuni'}
          </h5>
          <ul className="space-y-2 text-sm text-gray-500 dark:text-gray-400">
            <li>
              <Link href="/about" className="hover:text-primary transition-colors">
                {isEn ? 'About us' : 'Kuhusu Sisi'}
              </Link>
            </li>
            <li>
              <Link href="/about" className="hover:text-primary transition-colors">
                {isEn ? 'Our Blog' : 'Blogu Yetu'}
              </Link>
            </li>
            <li>
              <Link href="/contact" className="hover:text-primary transition-colors">
                {isEn ? 'Careers' : 'Kazi'}
              </Link>
            </li>
          </ul>
        </div>
        <div className="space-y-4">
          <h5 className="font-bold text-sm uppercase tracking-widest">
            {isEn ? 'Newsletter' : 'Barua pepe ya Habari'}
          </h5>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            {isEn
              ? 'Join our mailing list for exclusive deals.'
              : 'Jiunge na orodha yetu ya barua pepe kwa ofa za kipekee.'}
          </p>
          <form onSubmit={handleNewsletterSubmit} className="flex gap-2">
            <input 
              className="flex-1 h-10 px-3 bg-background-light dark:bg-gray-800 border-none rounded-lg text-sm" 
              placeholder={isEn ? 'Email' : 'Barua Pepe'} 
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
            <button type="submit" className="bg-primary text-white p-2 rounded-lg hover:bg-primary/90 transition-all">
              <span className="material-symbols-outlined">send</span>
            </button>
          </form>
        </div>
      </div>
      <div className="max-w-7xl mx-auto pt-12 mt-12 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-xs font-medium text-gray-400">
        <p>© 2024 SAFCO FinTech Dodoma. {isEn ? 'All rights reserved.' : 'Haki zote zimehifadhiwa.'}</p>
        <div className="flex gap-6">
          <Link href="/privacy-policy" className="hover:text-primary transition-colors">
            {isEn ? 'Privacy Policy' : 'Sera ya Faragha'}
          </Link>
          <Link href="/terms-of-service" className="hover:text-primary transition-colors">
            {isEn ? 'Terms of Service' : 'Masharti ya Huduma'}
          </Link>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
