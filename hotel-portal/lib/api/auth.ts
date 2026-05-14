import { getApiUrl, getAuthHeaders } from './config';

export interface LoginCredentials {
  phone: string;
  password: string;
}

export interface RegisterData {
  first_name: string;
  last_name: string;
  phone: string;
  email?: string;
  password: string;
  password_confirmation: string;
  room_id?: number; // Optional: if registering from room selection
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  data?: {
    user: {
      id: number;
      name: string;
      email?: string;
      phone: string;
    };
    token: string;
  };
}

export interface User {
  id: number;
  name?: string;
  first_name?: string;
  last_name?: string;
  email?: string;
  phone: string;
}

class AuthAPI {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    try {
      const response = await fetch(getApiUrl('/guest/login'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(credentials),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Login failed');
      }

      // Store token
      if (data.data?.token) {
        localStorage.setItem('auth_token', data.data.token);
      }

      return data;
    } catch (error: any) {
      console.error('Login error:', error);
      throw new Error(error.message || 'Login failed');
    }
  }

  async register(data: RegisterData): Promise<AuthResponse> {
    try {
      const response = await fetch(getApiUrl('/guest/register'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Registration failed');
      }

      // Store token
      if (result.data?.token) {
        localStorage.setItem('auth_token', result.data.token);
      }

      return result;
    } catch (error: any) {
      console.error('Registration error:', error);
      throw new Error(error.message || 'Registration failed');
    }
  }

  async logout(): Promise<void> {
    try {
      const token = localStorage.getItem('auth_token');
      if (token) {
        await fetch(getApiUrl('/guest/logout'), {
          method: 'POST',
          headers: getAuthHeaders(),
        });
      }
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('auth_token');
    }
  }

  async getCurrentUser(): Promise<User | null> {
    try {
      const token = localStorage.getItem('auth_token');
      if (!token) return null;

      const response = await fetch(getApiUrl('/guest/me'), {
        method: 'GET',
        headers: getAuthHeaders(),
      });

      if (!response.ok) {
        throw new Error('Failed to get user');
      }

      const data = await response.json();
      return data.data?.user || null;
    } catch (error) {
      console.error('Get current user error:', error);
      localStorage.removeItem('auth_token');
      return null;
    }
  }

  async updateProfile(data: {
    first_name?: string;
    last_name?: string;
    email?: string;
    phone?: string;
    password?: string;
    password_confirmation?: string;
  }): Promise<AuthResponse> {
    try {
      const response = await fetch(getApiUrl('/guest/profile'), {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Failed to update profile');
      }

      // Update stored token if new user data is returned
      if (result.data?.user) {
        // Token remains the same, but user data is updated
      }

      return result;
    } catch (error: any) {
      console.error('Update profile error:', error);
      throw new Error(error.message || 'Failed to update profile');
    }
  }

  isAuthenticated(): boolean {
    return !!localStorage.getItem('auth_token');
  }
}

export const authAPI = new AuthAPI();
