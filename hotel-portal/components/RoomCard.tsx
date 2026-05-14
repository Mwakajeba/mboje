'use client';

import React from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';

interface Room {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string;
  amenities: string[];
  capacity: number;
}

interface RoomCardProps {
  room: Room;
}

const RoomCard: React.FC<RoomCardProps> = ({ room }) => {
  const { isAuthenticated } = useAuth();

  return (
    <div className="bg-white dark:bg-background-dark rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
      <div className="relative h-48 w-full">
        <Image
          src={room.image}
          alt={room.name}
          fill
          className="object-cover"
        />
      </div>
      <div className="p-6">
        <h3 className="text-xl font-bold mb-2">{room.name}</h3>
        <p className="text-gray-600 dark:text-gray-400 text-sm mb-4">{room.description}</p>
        
        <div className="flex flex-wrap gap-2 mb-4">
          {room.amenities.slice(0, 4).map((amenity, index) => (
            <span
              key={index}
              className="text-xs bg-background-light dark:bg-gray-800 px-2 py-1 rounded"
            >
              {amenity}
            </span>
          ))}
        </div>
        
        <div className="flex items-center justify-between mb-4">
          <div>
            <span className="text-2xl font-black text-primary">Tsh {room.price.toLocaleString()}</span>
            <span className="text-sm text-gray-500">/ usiku</span>
          </div>
          <div className="flex items-center gap-1 text-sm text-gray-500">
            <span className="material-symbols-outlined text-base">people</span>
            <span>{room.capacity} watu</span>
          </div>
        </div>
        
        {isAuthenticated ? (
          <Link
            href={`/book/${room.id}`}
            className="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/90 transition-all flex items-center justify-center gap-2"
          >
            <span>Omba Sasa</span>
            <span className="material-symbols-outlined">arrow_forward</span>
          </Link>
        ) : (
          <Link
            href="/login"
            className="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/90 transition-all flex items-center justify-center gap-2"
          >
            <span>Ingia kuomba</span>
            <span className="material-symbols-outlined">login</span>
          </Link>
        )}
      </div>
    </div>
  );
};

export default RoomCard;
