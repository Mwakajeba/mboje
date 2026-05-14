'use client';

import React from 'react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { useLanguage } from '@/contexts/LanguageContext';

export default function TermsOfServicePage() {
  const { language } = useLanguage();
  const isEn = language === 'en';

  return (
    <div className="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
      <Header />
      <main className="flex-1 flex flex-col pt-24 pb-12">
        <div className="max-w-4xl mx-auto px-6 w-full">
          <h1 className="text-4xl font-black mb-4">
            {isEn ? 'Terms of Service' : 'Masharti ya Huduma'}
          </h1>
          <p className="text-gray-500 dark:text-gray-400 mb-8">
            {isEn ? 'Last updated: January 2024' : 'Imehakikiwa mwisho: Januari 2024'}
          </p>

          <div className="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-8 space-y-8">
            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '1. Acceptance of Terms' : '1. Kukubali Masharti'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'By accessing and using the SAFCO FinTech Dodoma hotel booking platform ("Platform"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, please do not use our Platform.'
                  : 'Kwa kufikia na kutumia jukwaa la kuomba hoteli la SAFCO FinTech Dodoma ("Jukwaa"), unakubali kufungwa na Masharti haya ya Huduma ("Masharti"). Ikiwa hukubali Masharti haya, tafadhali usitumie Jukwaa letu.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '2. Use of the Platform' : '2. Matumizi ya Jukwaa'}
              </h2>
              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '2.1 Eligibility' : '2.1 Kustahili'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'You must be at least 18 years old to use our Platform. By using the Platform, you represent and warrant that you are of legal age to form a binding contract.'
                  : 'Lazima uwe na angalau umri wa miaka 18 ili kutumia Jukwaa letu. Kwa kutumia Jukwaa, unawakilisha na kuthibitisha kuwa una umri wa kisheria wa kuunda mkataba unaofunga.'}
              </p>

              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '2.2 Account Registration' : '2.2 Usajili wa Akaunti'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'To make bookings, you must create an account. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.'
                  : 'Ili kufanya maombi, lazima uunde akaunti. Wewe ndiye mwenye jukumu la kudumisha siri ya hati za akaunti yako na kwa shughuli zote zinazotokea chini ya akaunti yako.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '3. Booking Terms' : '3. Masharti ya Kuomba'}
              </h2>
              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '3.1 Booking Process' : '3.1 Mchakato wa Kuomba'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'When you make a booking through our Platform, you enter into a contract directly with the hotel or accommodation provider. We act as an intermediary facilitating the booking process.'
                  : 'Unapofanya kuomba kupitia Jukwaa letu, unaingia mkataba moja kwa moja na hoteli au mtoa huduma za malazi. Sisi hufanya kazi kama mpatanishi unaowezesha mchakato wa kuomba.'}
              </p>

              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '3.2 Payment Terms' : '3.2 Masharti ya Malipo'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'Payment is required to confirm your booking. All prices are displayed in Tanzanian Shillings (TZS) unless otherwise stated. Payment must be completed within 2 hours of booking creation, or the booking will be automatically cancelled.'
                  : 'Malipo yanahitajika ili kuthibitisha kuomba kwako. Bei zote zinaonyeshwa kwa Shilingi za Tanzania (TZS) isipokuwa imesemwa vinginevyo. Malipo lazima yakamilishe ndani ya masaa 2 ya kuunda kuomba, au kuomba kutaighairiwa kiatomati.'}
              </p>

              <h3 className="text-xl font-bold mb-2 mt-4">
                {isEn ? '3.3 Cancellation and Refunds' : '3.3 Kughairi na Kurudisha Fedha'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'Cancellation policies vary by hotel and booking type. Free cancellation is available until 24 hours before check-in. Cancellations after this period or no-shows may be subject to fees. Refunds, if applicable, will be processed according to the hotel\'s cancellation policy.'
                  : 'Sera za kughairi hutofautiana kulingana na hoteli na aina ya kuomba. Kughairi bure inapatikana hadi masaa 24 kabla ya kuingia. Kughairi baada ya kipindi hiki au kutokuja kunaweza kulipwa ada. Kurudisha fedha, ikiwa inafaa, kutasindikizwa kulingana na sera ya kughairi ya hoteli.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '4. User Responsibilities' : '4. Majukumu ya Mtumiaji'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'You agree to:'
                  : 'Unakubali:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'Provide accurate and complete information' : 'Kutoa taarifa sahihi na kamili'}</li>
                <li>{isEn ? 'Use the Platform only for lawful purposes' : 'Kutumia Jukwaa tu kwa madhumuni ya kisheria'}</li>
                <li>{isEn ? 'Not engage in fraudulent or deceptive practices' : 'Kutojihusisha na mazoea ya udanganyifu au ya kudanganya'}</li>
                <li>{isEn ? 'Respect the intellectual property rights of others' : 'Kuheshimu haki za milki ya akili za wengine'}</li>
                <li>{isEn ? 'Not interfere with or disrupt the Platform\'s operation' : 'Kutokuvuruga au kuvuruga utendakazi wa Jukwaa'}</li>
              </ul>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '5. Limitation of Liability' : '5. Kikomo cha Jukumu'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'SAFCO FinTech Dodoma acts as an intermediary and is not responsible for the quality, safety, or availability of accommodations booked through our Platform. We are not liable for any damages, losses, or expenses arising from your use of the Platform or your stay at any accommodation.'
                  : 'SAFCO FinTech Dodoma hufanya kazi kama mpatanishi na haijajali ubora, usalama, au upatikanaji wa malazi yaliyoombwa kupitia Jukwaa letu. Hatujajali kwa uharibifu wowote, hasara, au gharama zinazotokana na matumizi yako ya Jukwaa au makao yako katika malazi yoyote.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '6. Intellectual Property' : '6. Milki ya Akili'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'All content, features, and functionality of the Platform, including but not limited to text, graphics, logos, and software, are owned by SAFCO FinTech Dodoma and are protected by copyright, trademark, and other intellectual property laws.'
                  : 'Maudhui yote, vipengele, na utendakazi wa Jukwaa, zikiwemo lakini si kwa kikomo maandishi, picha, nembo, na programu, ni mali ya SAFCO FinTech Dodoma na zinalindwa na hakimiliki, alama ya biashara, na sheria zingine za milki ya akili.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '7. Prohibited Activities' : '7. Shughuli Zinazoruhusiwa'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'You are prohibited from:'
                  : 'Hukuruhusiwa:'}
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400 ml-4">
                <li>{isEn ? 'Making false or fraudulent bookings' : 'Kufanya maombi ya uwongo au ya udanganyifu'}</li>
                <li>{isEn ? 'Using automated systems to access the Platform' : 'Kutumia mifumo ya kiatomati kufikia Jukwaa'}</li>
                <li>{isEn ? 'Attempting to gain unauthorized access to the Platform' : 'Kujaribu kupata ufikiaji usioidhinishwa kwa Jukwaa'}</li>
                <li>{isEn ? 'Transmitting viruses or malicious code' : 'Kutuma virusi au msimbo wa uovu'}</li>
                <li>{isEn ? 'Harassing or threatening other users' : 'Kuwatesa au kuwaogopesha watumiaji wengine'}</li>
              </ul>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '8. Modifications to Terms' : '8. Mabadiliko ya Masharti'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We reserve the right to modify these Terms at any time. Changes will be effective immediately upon posting on the Platform. Your continued use of the Platform after changes constitutes acceptance of the modified Terms.'
                  : 'Tunahifadhi haki ya kubadilisha Masharti haya wakati wowote. Mabadiliko yatakuwa na athari mara moja baada ya kuweka kwenye Jukwaa. Matumizi yako ya kuendelea ya Jukwaa baada ya mabadiliko yanajenga kukubali Masharti yaliyobadilishwa.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '9. Termination' : '9. Kukomesha'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'We reserve the right to terminate or suspend your account and access to the Platform at our sole discretion, without prior notice, for any violation of these Terms or for any other reason we deem necessary.'
                  : 'Tunahifadhi haki ya kukomesha au kusimamisha akaunti yako na ufikiaji wa Jukwaa kwa uamuzi wetu peke yetu, bila taarifa ya awali, kwa ukiukaji wowote wa Masharti haya au kwa sababu nyingine yoyote tunayoiona ni muhimu.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '10. Governing Law' : '10. Sheria Inayoongoza'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'These Terms are governed by and construed in accordance with the laws of Tanzania. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction of the courts of Tanzania.'
                  : 'Masharti haya yanaongozwa na kufasiriwa kulingana na sheria za Tanzania. Mzozo wowote unaotokana na Masharti haya utakuwa chini ya mamlaka ya pekee ya mahakama za Tanzania.'}
              </p>
            </section>

            <section>
              <h2 className="text-2xl font-bold mb-4">
                {isEn ? '11. Contact Information' : '11. Taarifa za Mawasiliano'}
              </h2>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                {isEn
                  ? 'If you have questions about these Terms, please contact us at:'
                  : 'Ikiwa una maswali kuhusu Masharti haya, tafadhali wasiliana nasi kwa:'}
              </p>
              <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <p className="font-bold mb-2">SAFCO FinTech Dodoma</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {isEn ? 'Email: legal@safco.com' : 'Barua Pepe: legal@safco.com'}
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
