'use client';

import React from 'react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useLanguage } from '@/contexts/LanguageContext';

export default function PrivacyPolicyPage() {
  const { language } = useLanguage();
  const isEn = language === 'en';

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-4xl mx-auto px-6 w-full">
          <h1 className="text-4xl font-black mb-4">
            {isEn ? 'Privacy Policy' : 'Sera ya Faragha'}
          </h1>
          <p className="text-gray-500 dark:text-gray-400 mb-8">
            {isEn ? 'Last updated: January 2024' : 'Imehakikiwa mwisho: Januari 2024'}
          </p>

          <div className="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-8 space-y-8">
            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '1. Introduction' : '1. Utangulizi'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'SAFCO FinTech Dodoma ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our hotel booking platform and services.'
                  : 'SAFCO FinTech Dodoma ("sisi," "yetu," au "tunao") imejitolea kulinda faragha yako. Sera hii ya Faragha inaeleza jinsi tunavyokusanya, kutumia, kufichua, na kulinda taarifa zako unapotumia jukwaa letu la kuomba hoteli na huduma.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '2. Information We Collect' : '2. Taarifa Tunazokusanya'}
              </h2>
              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '2.1 Personal Information' : '2.1 Taarifa za Kibinafsi'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We collect personal information that you provide directly to us, including:'
                  : 'Tunakusanya taarifa za kibinafsi ambazo unazitoa moja kwa moja, zikiwemo:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'Name, email address, and phone number' : 'Jina, anwani ya barua pepe, na nambari ya simu'}</li>
                <li>{isEn ? 'Payment information (processed securely through third-party payment processors)' : 'Taarifa za malipo (zinazosindikizwa kwa usalama kupitia wakusanyaji wa malipo wa watu wa tatu)'}</li>
                <li>{isEn ? 'Booking preferences and special requests' : 'Mapendeleo ya kuomba na maombi maalum'}</li>
                <li>{isEn ? 'Identification documents (when required for check-in)' : 'Hati za utambulisho (zinapohitajika kwa kuingia)'}</li>
              </ul>

              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '2.2 Usage Information' : '2.2 Taarifa za Matumizi'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We automatically collect certain information about your device and how you interact with our platform:'
                  : 'Tunakusanya moja kwa moja taarifa fulani kuhusu kifaa chako na jinsi unavyojihusisha na jukwaa letu:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'IP address and browser type' : 'Anwani ya IP na aina ya kivinjari'}</li>
                <li>{isEn ? 'Pages visited and time spent on our platform' : 'Kurasa zilizotembelewa na muda uliotumiwa kwenye jukwaa letu'}</li>
                <li>{isEn ? 'Device information and operating system' : 'Taarifa za kifaa na mfumo wa uendeshaji'}</li>
              </ul>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '3. How We Use Your Information' : '3. Jinsi Tunavyotumia Taarifa Zako'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We use the information we collect to:'
                  : 'Tunatumia taarifa tunazokusanya kwa:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'Process and manage your bookings' : 'Kusindikiza na kusimamia maombi yako'}</li>
                <li>{isEn ? 'Communicate with you about your reservations' : 'Kuwasiliana nawe kuhusu hifadhi zako'}</li>
                <li>{isEn ? 'Send booking confirmations and updates' : 'Kutuma uthibitisho wa kuomba na sasisho'}</li>
                <li>{isEn ? 'Provide customer support and respond to inquiries' : 'Kutoa msaada wa wateja na kujibu maswali'}</li>
                <li>{isEn ? 'Improve our services and platform functionality' : 'Kuboresha huduma zetu na utendakazi wa jukwaa'}</li>
                <li>{isEn ? 'Send marketing communications (with your consent)' : 'Kutuma mawasiliano ya uuzaji (kwa idhini yako)'}</li>
                <li>{isEn ? 'Comply with legal obligations' : 'Kufuata majukumu ya kisheria'}</li>
              </ul>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '4. Information Sharing and Disclosure' : '4. Kushiriki na Kufichua Taarifa'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We do not sell your personal information. We may share your information only in the following circumstances:'
                  : 'Hatuziuzi taarifa zako za kibinafsi. Tunaweza kushiriki taarifa zako tu katika hali zifuatazo:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'With hotels and accommodation providers to fulfill your bookings' : 'Na hoteli na watoa huduma za malazi ili kutimiza maombi yako'}</li>
                <li>{isEn ? 'With payment processors to process transactions' : 'Na wakusanyaji wa malipo ili kusindikiza manunuzi'}</li>
                <li>{isEn ? 'When required by law or to protect our legal rights' : 'Inapohitajika na sheria au kulinda haki zetu za kisheria'}</li>
                <li>{isEn ? 'With your explicit consent' : 'Kwa idhini yako ya wazi'}</li>
              </ul>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '5. Data Security' : '5. Usalama wa Data'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.'
                  : 'Tunatekeleza hatua za kiufundi na za kikundi zinazofaa kulinda taarifa zako za kibinafsi dhidi ya ufikiaji usioidhinishwa, mabadiliko, ufichuaji, au uharibifu. Hata hivyo, hakuna njia ya uwasilishaji kupitia intaneti ambayo ni salama 100%.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '6. Your Rights' : '6. Haki Zako'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'You have the right to:'
                  : 'Una haki ya:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'Access and review your personal information' : 'Kupata na kukagua taarifa zako za kibinafsi'}</li>
                <li>{isEn ? 'Request correction of inaccurate information' : 'Kuomba kusahihishwa kwa taarifa zisizo sahihi'}</li>
                <li>{isEn ? 'Request deletion of your personal information' : 'Kuomba kufutwa kwa taarifa zako za kibinafsi'}</li>
                <li>{isEn ? 'Opt-out of marketing communications' : 'Kujiondoa kutoka mawasiliano ya uuzaji'}</li>
                <li>{isEn ? 'Withdraw consent at any time' : 'Kujiondoa idhini wakati wowote'}</li>
              </ul>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '7. Cookies and Tracking Technologies' : '7. Vidakuzi na Teknolojia za Ufuatiliaji'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We use cookies and similar tracking technologies to enhance your experience, analyze usage patterns, and personalize content. You can control cookies through your browser settings.'
                  : 'Tunatumia vidakuzi na teknolojia zinazofanana za ufuatiliaji ili kuboresha uzoefu wako, kuchambua mifumo ya matumizi, na kubinafsisha maudhui. Unaweza kudhibiti vidakuzi kupitia mipangilio ya kivinjari chako.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '8. Children\'s Privacy' : '8. Faragha ya Watoto'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children.'
                  : 'Huduma zetu hazikusudiwa kwa watu chini ya umri wa miaka 18. Hatukusanyi kwa makusudi taarifa za kibinafsi kutoka kwa watoto.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '9. Changes to This Policy' : '9. Mabadiliko ya Sera Hii'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.'
                  : 'Tunaweza kusasisha Sera hii ya Faragha mara kwa mara. Tutakujulisha mabadiliko yoyote kwa kuweka sera mpya kwenye ukurasa huu na kusasisha tarehe ya "Imehakikiwa mwisho".'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '10. Contact Us' : '10. Wasiliana Nasi'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'If you have questions or concerns about this Privacy Policy, please contact us at:'
                  : 'Ikiwa una maswali au wasiwasi kuhusu Sera hii ya Faragha, tafadhali wasiliana nasi kwa:'}
              </p>
              <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <p className="font-bold mb-2">SAFCO FinTech Dodoma</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {isEn ? 'Email: privacy@safco.com' : 'Barua Pepe: privacy@safco.com'}
                </p>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {isEn ? 'Phone: +255 XXX XXX XXX' : 'Simu: +255 XXX XXX XXX'}
                </p>
              </div>
            </section>
          </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}
