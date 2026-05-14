'use client';

import React from 'react';
import { useLanguage } from '@/contexts/LanguageContext';

interface Destination {
  id: number;
  name: string;
  location: string;
  rating: string;
  imageUrl: string;
  imageAlt: string;
}

const destinations: Destination[] = [
  {
    id: 1,
    name: "Modern Forest Retreat",
    location: "Oslo, Norway",
    rating: "4.9 ★",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuDQPPhUsa7ZqT5XGmd_H2tWbfuCuxp3cf1k95nPZlsdVGkri0dfm9WpLSQcGXi7nLujWgTXOlLhhhpoZkVj9-Y7Gf-xJ0sn-T3o7IHqeXqRFJ2GYBlPOFxv_0EfYKzFd2mGH2CQ06qr8_abDWIKgu2J0kXZ9mqkb9Zq2T6wDmmVHPMG3KFkPwS5aLVj5bCBiElDQQkDVwJ6SnJXzZDHqiEC0CjS2ll8vBx8gXp-NqPCzmfz3Ov0JvkZfoLFzJEq3xQoXrJrKPXaB54",
    imageAlt: "Modern cabin in a forest during autumn"
  },
  {
    id: 2,
    name: "Skyline Executive Suite",
    location: "Tokyo, Japan",
    rating: "4.8 ★",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuDcPwKSG_qYbw-04BfA3droSCpwi_BoQDbGAWf4oBjX_dh5KL6iX9etLkl7oPyPum8bR_B66Pill27fko1G9nvUVK4Dyc7gD4NWklRvgVhRd3VLuXmySJJywRpBm0TA0AJ2XPOFPRg0s4EUoi23xqWi6Hw9dRpbTqONEiYnWrxbPDuEvE53dbMBwixY5iA6CngIQAA4jNGRgrKp1I745Smxe5CQMiRc6hVDpeMd6zvC7CSDKEPpXWXx_ZGKTXroSW4ZziHSx4TdDiw",
    imageAlt: "Luxury city hotel room with floor-to-ceiling windows"
  },
  {
    id: 3,
    name: "Azure Coast Resort",
    location: "Santorini, Greece",
    rating: "5.0 ★",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuA7Z2zhVyVHBKFwDjYYW-mtJRsixGR-287vNBBchuBFySCXSmEvPQ9rK-qhbVQ8vonbB3Vg-Svy-WMrRyXs43AbBkdNXpgpdgevoGtWqtHIWlPyyqXeTG5kJf-RyCpjq54Zabb-HLYItv8U4LofMvNPltqXeOgTLWzuz2P6PXm1o97AK8YMDN1Aa7Om6No7uJeGECsieqcJJBfRKg8tWnY9HQ9p5pxKP6gA88EORpu2x6mJAYvPBgwW0TafD89Vvyn8TEjJqKGU7gU",
    imageAlt: "Resort pool area with palm trees and sunbeds"
  }
];

const FeaturedDestinations: React.FC = () => {
  const { language } = useLanguage();
  const isEn = language === 'en';

  return (
    <section className="max-w-7xl mx-auto px-6 py-20 w-full">
      <div className="flex items-end justify-between mb-10">
        <div className="space-y-2">
          <span className="text-primary font-bold text-sm tracking-widest uppercase">
            {isEn ? 'Popular Destinations' : 'Maeneo Maarufu'}
          </span>
          <h3 className="text-3xl font-black">
            {isEn ? 'Escape to your next adventure' : 'Toka kwenye safari yako ijayo'}
          </h3>
        </div>
        <button className="text-primary font-bold flex items-center gap-2 hover:underline">
          {isEn ? 'View all' : 'Angalia zote'} <span className="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {destinations.map((destination) => (
          <div key={destination.id} className="group cursor-pointer">
            <div className="relative aspect-[4/3] rounded-xl overflow-hidden shadow-lg mb-4">
              <div 
                className="absolute inset-0 bg-cover bg-center group-hover:scale-105 transition-transform duration-500" 
                style={{ backgroundImage: `url('${destination.imageUrl}')` }}
                aria-label={destination.imageAlt}
              ></div>
              <div className="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-gray-900">
                {destination.rating}
              </div>
            </div>
            <h4 className="text-xl font-bold mb-1">{destination.name}</h4>
            <p className="text-gray-500 dark:text-gray-400 text-sm">{destination.location}</p>
          </div>
        ))}
      </div>
    </section>
  );
};

export default FeaturedDestinations;
