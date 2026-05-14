import { getApiUrl, getAuthHeaders } from './config';

export interface BookingData {
  room_id: number;
  check_in: string;
  check_out: string;
  adults: number;
  children: number;
  special_requests?: string;
}

export interface PaymentHistoryItem {
  id: number;
  date: string;
  amount: number;
  type: string;
  bank_account?: string;
  description?: string;
}

export interface Booking {
  id: number;
  hashid?: string; // Hash ID for the booking
  booking_number?: string;
  room_id: number;
  guest_id: number;
  check_in: string;
  check_out: string;
  adults: number;
  children: number;
  nights?: number;
  room_rate?: number;
  total_price?: number; // Optional, may come as total_amount from API
  total_amount?: number; // API field name
  paid_amount?: number;
  balance_due?: number;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed' | 'online_booking';
  payment_status?: string;
  special_requests?: string;
  created_at: string;
  updated_at: string;
  payment_history?: PaymentHistoryItem[];
  room?: {
    id: number;
    hashid?: string; // Hash ID for the room
    name: string;
    room_number?: string;
    type: string;
    description?: string;
    amenities?: string[];
    images?: string[];
  };
  branch?: {
    id: number;
    name: string;
    address?: string;
    phone?: string;
    email?: string;
  };
}

export interface BookingResponse {
  success: boolean;
  data: Booking;
  message?: string;
}

export interface BookingsResponse {
  success: boolean;
  data: Booking[];
  message?: string;
}

class BookingsAPI {
  async createBooking(bookingData: BookingData): Promise<Booking> {
    try {
      const response = await fetch(getApiUrl('/bookings'), {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(bookingData),
      });

      const data: BookingResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to create booking');
      }

      return data.data;
    } catch (error: any) {
      console.error('Create booking error:', error);
      throw new Error(error.message || 'Failed to create booking');
    }
  }

  async getMyBookings(branchId?: number): Promise<Booking[]> {
    try {
      const params = new URLSearchParams();
      if (branchId) {
        params.append('branch_id', branchId.toString());
      }
      const url = params.toString() ? `${getApiUrl('/bookings')}?${params.toString()}` : getApiUrl('/bookings');
      const response = await fetch(url, {
        method: 'GET',
        headers: getAuthHeaders(),
      });

      const data: BookingsResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch bookings');
      }

      return data.data || [];
    } catch (error) {
      console.error('Get bookings error:', error);
      throw error;
    }
  }

  async getBookingById(id: number | string): Promise<Booking> {
    try {
      const response = await fetch(getApiUrl(`/bookings/${id}`), {
        method: 'GET',
        headers: getAuthHeaders(),
      });

      const data: BookingResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch booking');
      }

      return data.data;
    } catch (error) {
      console.error('Get booking error:', error);
      throw error;
    }
  }

  async cancelBooking(id: number | string): Promise<void> {
    try {
      const response = await fetch(getApiUrl(`/bookings/${id}/cancel`), {
        method: 'POST',
        headers: getAuthHeaders(),
      });

      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Failed to cancel booking');
      }
    } catch (error) {
      console.error('Cancel booking error:', error);
      throw error;
    }
  }

  async downloadReceipt(id: number | string): Promise<void> {
    try {
      const response = await fetch(getApiUrl(`/bookings/${id}/receipt`), {
        method: 'GET',
        headers: getAuthHeaders(),
      });

      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Failed to download receipt');
      }

      // Get filename from Content-Disposition header or use default
      const contentDisposition = response.headers.get('Content-Disposition');
      let filename = 'receipt.pdf';
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }

      // Create blob and download
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Download receipt error:', error);
      throw error;
    }
  }
}

export const bookingsAPI = new BookingsAPI();
