import { getApiUrl } from './config';

export interface CompanySettings {
  id: number;
  name: string;
  email: string;
  phone: string;
  address: string;
  logo: string | null;
}

export interface CompanySettingsResponse {
  success: boolean;
  data: CompanySettings;
  message?: string;
}

class SettingsAPI {
  async getCompanySettings(): Promise<CompanySettings> {
    try {
      const response = await fetch(getApiUrl('/settings/company'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      const data: CompanySettingsResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch company settings');
      }

      return data.data;
    } catch (error) {
      console.error('Get company settings error:', error);
      throw error;
    }
  }
}

export const settingsAPI = new SettingsAPI();
