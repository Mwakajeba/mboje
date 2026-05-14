'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { authAPI, User } from '@/lib/api/auth';

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (phone: string, password: string) => Promise<void>;
  signup: (firstName: string, lastName: string, phone: string, email: string, password: string, confirmPassword: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  updateLastActivity: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

const SESSION_TIMEOUT = 15 * 60 * 1000; // 15 minutes in milliseconds
const LAST_ACTIVITY_KEY = 'last_activity';
const REDIRECT_URL_KEY = 'redirect_url';

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const router = useRouter();
  const pathname = usePathname();

  // Save current page URL before redirecting to login
  const saveCurrentPage = useCallback(() => {
    if (typeof window !== 'undefined') {
      const currentPath = window.location.pathname + window.location.search;
      // Don't save login/signup pages
      if (!currentPath.startsWith('/login') && !currentPath.startsWith('/signup')) {
        sessionStorage.setItem(REDIRECT_URL_KEY, currentPath);
      }
    }
  }, []);

  // Update last activity timestamp
  const updateLastActivity = useCallback(() => {
    if (typeof window !== 'undefined') {
      localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString());
    }
  }, []);

  // Check session timeout
  const checkSessionTimeout = useCallback(() => {
    if (typeof window === 'undefined' || !isAuthenticated) return;

    const lastActivity = localStorage.getItem(LAST_ACTIVITY_KEY);
    if (!lastActivity) {
      updateLastActivity();
      return;
    }

    const timeSinceLastActivity = Date.now() - parseInt(lastActivity);
    if (timeSinceLastActivity > SESSION_TIMEOUT) {
      // Session expired - logout and redirect to login
      saveCurrentPage();
      setUser(null);
      setIsAuthenticated(false);
      localStorage.removeItem('auth_token');
      localStorage.removeItem(LAST_ACTIVITY_KEY);
      router.push('/login');
    }
  }, [isAuthenticated, router, saveCurrentPage, updateLastActivity]);

  useEffect(() => {
    // Check if user is logged in on mount
    const checkAuth = async () => {
      if (authAPI.isAuthenticated()) {
        try {
          const currentUser = await authAPI.getCurrentUser();
          if (currentUser) {
            setUser(currentUser);
            setIsAuthenticated(true);
            updateLastActivity();
            
            // If we're on login/signup page and authenticated, redirect to saved page or dashboard
            // Use a longer delay to let the login/signup page handle the redirect first
            // This prevents race conditions where both try to redirect
            if (typeof window !== 'undefined' && (pathname === '/login' || pathname === '/signup')) {
              setTimeout(() => {
                // Check if booking data still exists (if login page already handled it, it will be cleared)
                const bookingData = sessionStorage.getItem('pending_booking');
                if (bookingData) {
                  // Only redirect if login page hasn't already handled it
                  let redirectUrl: string | null = null;
                  
                  try {
                    const booking = JSON.parse(bookingData);
                    console.log('AuthContext: Found booking data:', booking);
                    // Use hashid if available, otherwise use numeric ID
                    const roomIdentifier = booking.room_hashid || booking.room_id;
                    redirectUrl = `/book/${roomIdentifier}?checkIn=${booking.checkIn}&checkOut=${booking.checkOut}&adults=${booking.adults}&children=${booking.children}`;
                    console.log('AuthContext: Redirecting to booking page:', redirectUrl);
                    sessionStorage.removeItem('pending_booking');
                  } catch (e) {
                    console.error('Failed to parse booking data:', e);
                  }
                  
                  if (redirectUrl) {
                    router.replace(redirectUrl);
                    return;
                  }
                }
                
                // Fallback to saved redirect URL if no booking data
                const redirectUrl = getRedirectUrl();
                if (redirectUrl) {
                  clearRedirectUrl();
                  router.replace(redirectUrl);
                } else {
                  router.replace('/dashboard');
                }
              }, 200);
            }
          } else {
            // Token exists but user fetch failed - clear token
            localStorage.removeItem('auth_token');
            localStorage.removeItem(LAST_ACTIVITY_KEY);
          }
        } catch (error) {
          console.error('Failed to get current user:', error);
          localStorage.removeItem('auth_token');
          localStorage.removeItem(LAST_ACTIVITY_KEY);
        }
      }
      setIsLoading(false);
    };

    checkAuth();
  }, [updateLastActivity, pathname, router]);

  // Set up activity tracking and session timeout checking
  useEffect(() => {
    if (!isAuthenticated) return;

    // Update last activity on user interactions
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    const updateActivity = () => updateLastActivity();

    events.forEach((event) => {
      document.addEventListener(event, updateActivity, { passive: true });
    });

    // Check session timeout every minute
    const timeoutCheckInterval = setInterval(checkSessionTimeout, 60000);

    // Initial timeout check
    checkSessionTimeout();

    return () => {
      events.forEach((event) => {
        document.removeEventListener(event, updateActivity);
      });
      clearInterval(timeoutCheckInterval);
    };
  }, [isAuthenticated, checkSessionTimeout, updateLastActivity]);

  const login = async (phone: string, password: string) => {
    try {
      const response = await authAPI.login({ phone, password });
      if (response.data?.user && response.data?.token) {
        setUser(response.data.user);
        setIsAuthenticated(true);
        updateLastActivity();
      } else {
        throw new Error(response.message || 'Login failed');
      }
    } catch (error: any) {
      console.error('Login error:', error);
      throw error;
    }
  };

  const signup = async (firstName: string, lastName: string, phone: string, email: string, password: string, confirmPassword: string) => {
    try {
      // Get room_id from pending_booking if available
      let roomId: number | undefined = undefined;
      if (typeof window !== 'undefined') {
        const bookingData = sessionStorage.getItem('pending_booking');
        if (bookingData) {
          try {
            const booking = JSON.parse(bookingData);
            roomId = booking.room_id;
          } catch (e) {
            console.error('Failed to parse booking data for room_id:', e);
          }
        }
      }
      
      const response = await authAPI.register({
        first_name: firstName,
        last_name: lastName,
        phone,
        email: email || undefined,
        password,
        password_confirmation: confirmPassword,
        room_id: roomId,
      });
      if (response.data?.user && response.data?.token) {
        setUser(response.data.user);
        setIsAuthenticated(true);
        updateLastActivity();
      } else {
        throw new Error(response.message || 'Registration failed');
      }
    } catch (error: any) {
      console.error('Signup error:', error);
      throw error;
    }
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setIsAuthenticated(false);
      localStorage.removeItem('auth_token');
      localStorage.removeItem(LAST_ACTIVITY_KEY);
      sessionStorage.removeItem(REDIRECT_URL_KEY);
    }
  };

  const refreshUser = async () => {
    if (authAPI.isAuthenticated()) {
      try {
        const currentUser = await authAPI.getCurrentUser();
        if (currentUser) {
          setUser(currentUser);
          setIsAuthenticated(true);
        } else {
          setUser(null);
          setIsAuthenticated(false);
        }
      } catch (error) {
        console.error('Refresh user error:', error);
        setUser(null);
        setIsAuthenticated(false);
      }
    }
  };

  return (
    <AuthContext.Provider value={{ user, isAuthenticated, isLoading, login, signup, logout, refreshUser, updateLastActivity }}>
      {children}
    </AuthContext.Provider>
  );
};

// Helper function to get redirect URL
export const getRedirectUrl = (): string | null => {
  if (typeof window === 'undefined') return null;
  return sessionStorage.getItem(REDIRECT_URL_KEY);
};

// Helper function to clear redirect URL
export const clearRedirectUrl = (): void => {
  if (typeof window !== 'undefined') {
    sessionStorage.removeItem(REDIRECT_URL_KEY);
  }
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
