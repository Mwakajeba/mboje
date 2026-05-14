import { getApiUrl } from './config';

export interface BankAccount {
  id: number;
  name: string;
  bank_name?: string;
  account_number: string;
  currency: string;
  balance: number;
}

export interface BankAccountsResponse {
  success: boolean;
  data: {
    bank_accounts: BankAccount[];
  };
  message?: string;
}

class BankAccountsAPI {
  async getBankAccounts(): Promise<BankAccount[]> {
    try {
      const response = await fetch(getApiUrl('/guest/bank-accounts'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      const data: BankAccountsResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch bank accounts');
      }

      return data.data?.bank_accounts || [];
    } catch (error: any) {
      console.error('Get bank accounts error:', error);
      throw new Error(error.message || 'Failed to fetch bank accounts');
    }
  }
}

export const bankAccountsAPI = new BankAccountsAPI();
