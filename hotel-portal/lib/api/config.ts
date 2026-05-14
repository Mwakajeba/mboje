// API Configuration
export const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://127.0.0.1:8000/api';

export const API_ENDPOINTS = {
  // Authentication
  login: '/guest/login',
  register: '/guest/register',
  logout: '/guest/logout',
  me: '/guest/me',
  
  // Rooms
  rooms: '/rooms',
  roomDetails: (id: string | number) => `/rooms/${id}`,
  
  // Bookings
  bookings: '/bookings',
  createBooking: '/bookings',
  bookingDetails: (id: string | number) => `/bookings/${id}`,
  
  // Guests
  guests: '/guests',
};

// Helper function to get full API URL
export const getApiUrl = (endpoint: string): string => {
  return `${API_BASE_URL}${endpoint}`;
};

// Helper function to get headers with auth token
export const getAuthHeaders = (): HeadersInit => {
  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token && { Authorization: `Bearer ${token}` }),
  };
};
