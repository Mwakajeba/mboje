import { getApiUrl, getAuthHeaders } from './config';

export interface Room {
  id: number;
  hashid?: string;
  name: string;
  room_number?: string;
  type: string;
  price: number;
  max_adults: number;
  max_children: number;
  status: 'available' | 'booked' | 'maintenance' | 'out_of_order';
  description?: string;
  amenities?: string[];
  images?: string[];
  created_at?: string;
  updated_at?: string;
}

export interface RoomsResponse {
  success: boolean;
  data: Room[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
  message?: string;
}

export interface RoomResponse {
  success: boolean;
  data: Room;
  message?: string;
}

// Mock data fallback when API is not available
const getMockRooms = (): Room[] => [
  {
    id: 1,
    name: 'Deluxe Ocean Suite',
    room_number: '101',
    type: 'suite',
    price: 280000,
    max_adults: 2,
    max_children: 2,
    status: 'available',
    description: 'Luxurious suite with ocean view',
    amenities: ['Free WiFi', 'AC', 'Smart TV', 'Bathtub', 'Private Balcony'],
    images: ['https://lh3.googleusercontent.com/aida-public/AB6AXuDSuuvnCqPoMlLVT4YBHVYVqQ2reFG0KMDMhvJk4dPVJqU2ROPs5dLeNPI9-P1ws6CdrXgeS9Tjs2PCcXzrA2nvvJCXv-HH6h6HkIaJ2IYtvM0CpqBOsGJkwAZ8U4JrdgHZV3SlMh7BYyI6SnTrPXzPX3W581baarzzUe3xfMmQPqcBoJ-OT3fxwPfvPSvTZ-r7qcQzC2GMYLP2Vvwe9EdIjWI5SKy6aGmY9ThFv4dpUSxmmJcndQOhXv6vkeUmLPKE-Q7tu1Mv5Ak'],
  },
  {
    id: 2,
    name: 'Standard King Room',
    room_number: '201',
    type: 'standard',
    price: 120000,
    max_adults: 2,
    max_children: 1,
    status: 'available',
    description: 'Comfortable standard room',
    amenities: ['Free WiFi', 'AC', 'TV', 'Mini-bar'],
    images: ['https://lh3.googleusercontent.com/aida-public/AB6AXuDA04Jp1NeoVNemlrmNQHVRlhkq4_4aHX8ECC7FRjeYYvUeYXboxuRpghfD17N9IwqR6I9KFfgtqZ45km0V7czj9FGmkGjZAml3dpOXCVBYmOJgpe5DGQsYf3FZ9_LscxyD0popILc6BiLYhXqSp27V2D7o2n6dHwNNGOxKdRuxsy45FjXxnWlsYyINlZMwmKGLXCO1BIH_bQSopjGh8R3fmCp9VCDWiR5Z9UEClM7Qsnfa52Fvhe9dngLkb2RPjDAmrCSSToYWnmU'],
  },
  {
    id: 3,
    name: 'Executive King Suite',
    room_number: '301',
    type: 'suite',
    price: 350000,
    max_adults: 2,
    max_children: 0,
    status: 'booked',
    description: 'Premium executive suite',
    amenities: ['Free WiFi', 'Workspace', 'Nespresso'],
    images: ['https://lh3.googleusercontent.com/aida-public/AB6AXuDNDbsElt4KIbGY1mlZ6xkXh-XTrTIiax3RAGmn42eu_EzshmkCgoJW54F_Bmw-QCfbXU9QgNiFL-vjDDNHV583D6dMZoGWqsxludhgbJgpTGU9pKBgun3n-DkcDlT7FIuR8gIByqSU41w9LmNv8ppnrGG9RNHLqSqRWcmrM7eWCheaDOySkfoMAlOeb9JZ8RtREIKhWuv16t0wW6sgoQm3GUrawSs3sl8lUqujUY1xZ5gq_DG-Dqsu9rMZkx7-C8jD09Rib27Tu_4'],
  },
  {
    id: 4,
    name: 'Superior Twin Room',
    room_number: '401',
    type: 'superior',
    price: 150000,
    max_adults: 2,
    max_children: 0,
    status: 'available',
    description: 'Spacious twin room',
    amenities: ['Free WiFi', 'AC', 'Rain Shower'],
    images: ['https://lh3.googleusercontent.com/aida-public/AB6AXuAcJZWmm6QXcwb3zVK0bc-S2y8Fba827_lxS0A1yKvxLm1O2XBy6GF841621RtvVmTB6AJbNB-VzwspNm8cLwDzMOwssN9b-vXR-YITjO6dN-a-L9mEKxGHz7AG-jj9sLJx2G36SODDtRrroLJXJkXRXRYdqFUQ5fqqdJTa38CvSv3siT08YWkWmMYwbQav-VQhXlj5NAH543eQYU0gYVh1PoUb27ialnmiObXL366WNlD2-q0ufmHACDe0Vs6gAYtYOaktf-TXRCw'],
  },
];

class RoomsAPI {
  async getAllRooms(page: number = 1, perPage: number = 12): Promise<{ rooms: Room[]; pagination?: RoomsResponse['pagination'] }> {
    try {
      const params = new URLSearchParams();
      params.append('page', page.toString());
      params.append('per_page', perPage.toString());
      
      const response = await fetch(getApiUrl('/rooms') + '?' + params.toString(), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      // If route not found (404), use mock data as fallback
      if (response.status === 404) {
        console.warn('API route /api/rooms not found. Please set up Laravel API routes. Using mock data as fallback.');
        return { rooms: getMockRooms() };
      }

      // Handle other HTTP errors
      if (!response.ok) {
        const errorText = await response.text();
        let errorData;
        try {
          errorData = JSON.parse(errorText);
        } catch {
          errorData = { message: errorText };
        }
        
        // If it's a 404 or route not found, use mock data
        if (response.status === 404 || errorData.message?.includes('could not be found')) {
          console.warn('API route not found, using mock data');
          return { rooms: getMockRooms() };
        }
        
        throw new Error(errorData.message || `HTTP ${response.status}: Failed to fetch rooms`);
      }

      const data: RoomsResponse = await response.json();

      // Return real data from Laravel
      if (data.success && data.data) {
        return { rooms: data.data, pagination: data.pagination };
      }

      // If response format is unexpected, try to extract data
      if (Array.isArray(data)) {
        return { rooms: data };
      }

      return { rooms: [] };
    } catch (error: any) {
      // If it's a network error or route not found, use mock data as fallback
      if (
        error.message?.includes('Failed to fetch') ||
        error.message?.includes('404') ||
        error.message?.includes('could not be found') ||
        error.message?.includes('NetworkError')
      ) {
        console.warn('API not available, using mock data as fallback:', error.message);
        console.warn('To connect to Laravel API, please:');
        console.warn('1. Create RoomApiController in app/Http/Controllers/Api/');
        console.warn('2. Add API routes in routes/api.php');
        console.warn('3. See LARAVEL_API_SETUP.md for details');
        return { rooms: getMockRooms() };
      }
      console.error('Get rooms error:', error);
      throw error;
    }
  }

  async getRoomById(id: number | string): Promise<Room> {
    try {
      const response = await fetch(getApiUrl(`/rooms/${id}`), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      // If route not found (404), use mock data
      if (response.status === 404) {
        console.warn('API route not found, using mock data');
        const mockRooms = getMockRooms();
        const room = mockRooms.find((r) => r.id === parseInt(id as string));
        if (room) return room;
        throw new Error('Room not found');
      }

      const data: RoomResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch room');
      }

      return data.data;
    } catch (error: any) {
      // If it's a network error or route not found, try mock data
      if (error.message?.includes('Failed to fetch') || error.message?.includes('404') || error.message?.includes('could not be found')) {
        console.warn('API not available, using mock data:', error.message);
        const mockRooms = getMockRooms();
        const room = mockRooms.find((r) => r.id === parseInt(id as string));
        if (room) return room;
      }
      console.error('Get room error:', error);
      throw error;
    }
  }

  async getAvailableRooms(checkIn?: string, checkOut?: string, page: number = 1, perPage: number = 12, branchId?: number): Promise<{ rooms: Room[]; pagination?: RoomsResponse['pagination'] }> {
    try {
      if (!checkIn || !checkOut) {
        // If no dates provided, use regular rooms endpoint
        return this.getAllRooms(page, perPage);
      }

      const params = new URLSearchParams();
      params.append('check_in', checkIn);
      params.append('check_out', checkOut);
      params.append('page', page.toString());
      params.append('per_page', perPage.toString());
      if (branchId) {
        params.append('branch_id', branchId.toString());
      }

      // Use the bookings/available-rooms endpoint
      const url = getApiUrl('/bookings/available-rooms') + `?${params.toString()}`;
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      // If route not found (404), fallback to rooms endpoint
      if (response.status === 404) {
        console.warn('Bookings API route not found, falling back to rooms endpoint');
        const params2 = new URLSearchParams();
        params2.append('check_in', checkIn);
        params2.append('check_out', checkOut);
        params2.append('page', page.toString());
        params2.append('per_page', perPage.toString());
        const fallbackUrl = getApiUrl('/rooms') + `?${params2.toString()}`;
        const fallbackResponse = await fetch(fallbackUrl, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        });
        
        if (fallbackResponse.ok) {
          const fallbackData: RoomsResponse = await fallbackResponse.json();
          return { rooms: fallbackData.data || [], pagination: fallbackData.pagination };
        }
        
        return { rooms: getMockRooms().filter((room) => room.status === 'available') };
      }

      const data: RoomsResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch available rooms');
      }

      // Preserve status exactly as returned from API - don't override
      const rooms = (data.data || []).map(room => {
        // Log for debugging
        if (process.env.NODE_ENV === 'development') {
          console.log(`API Room ${room.id}: status = ${room.status}`);
        }
        return {
          ...room,
          // Keep the status exactly as returned - don't default to 'available'
          status: room.status || 'available',
        };
      });

      return { rooms, pagination: data.pagination };
    } catch (error: any) {
      // If it's a network error or route not found, try fallback
      if (error.message?.includes('Failed to fetch') || error.message?.includes('404') || error.message?.includes('could not be found')) {
        console.warn('Bookings API not available, trying fallback:', error.message);
        
        // Try fallback to rooms endpoint
        if (checkIn && checkOut) {
          try {
            const params = new URLSearchParams();
            params.append('check_in', checkIn);
            params.append('check_out', checkOut);
            params.append('page', page.toString());
            params.append('per_page', perPage.toString());
            const fallbackUrl = getApiUrl('/rooms') + `?${params.toString()}`;
            const fallbackResponse = await fetch(fallbackUrl, {
              method: 'GET',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
              },
            });
            
            if (fallbackResponse.ok) {
              const fallbackData: RoomsResponse = await fallbackResponse.json();
              return { rooms: fallbackData.data || [], pagination: fallbackData.pagination };
            }
          } catch (fallbackError) {
            console.warn('Fallback also failed, using mock data');
          }
        }
        
        return { rooms: getMockRooms().filter((room) => room.status === 'available') };
      }
      console.error('Get available rooms error:', error);
      throw error;
    }
  }
}

export const roomsAPI = new RoomsAPI();
