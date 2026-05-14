import { getApiUrl } from './config';

export interface Branch {
  id: number;
  name: string;
  address?: string;
  phone?: string;
  email?: string;
}

export interface BranchesResponse {
  success: boolean;
  data: {
    branches: Branch[];
  };
  message?: string;
}

class BranchesAPI {
  async getBranches(): Promise<Branch[]> {
    try {
      const response = await fetch(getApiUrl('/guest/branches'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      const data: BranchesResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch branches');
      }

      return data.data?.branches || [];
    } catch (error: any) {
      console.error('Get branches error:', error);
      throw new Error(error.message || 'Failed to fetch branches');
    }
  }
}

export const branchesAPI = new BranchesAPI();
