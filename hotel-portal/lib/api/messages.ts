import { getApiUrl, getAuthHeaders } from './config';

export interface MessageData {
  name: string;
  email: string;
  subject: string;
  message: string;
  branch_id?: number;
  source?: 'about_page' | 'contact_page';
}

export interface MessageResponse {
  success: boolean;
  message?: string;
  data?: {
    id: number;
    name: string;
    email: string;
    subject: string;
    message: string;
    branch_id?: number;
    source?: string;
    created_at: string;
  };
}

class MessagesAPI {
  async sendMessage(data: MessageData): Promise<MessageResponse['data']> {
    try {
      const response = await fetch(getApiUrl('/guest/messages'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(data),
      });

      const result: MessageResponse = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Failed to send message');
      }

      return result.data;
    } catch (error: any) {
      console.error('Send message error:', error);
      throw new Error(error.message || 'Failed to send message');
    }
  }

  async getMyMessages(): Promise<any[]> {
    try {
      const response = await fetch(getApiUrl('/guest/messages'), {
        method: 'GET',
        headers: getAuthHeaders(),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Failed to fetch messages');
      }

      return result.data?.messages || [];
    } catch (error: any) {
      console.error('Get messages error:', error);
      throw new Error(error.message || 'Failed to fetch messages');
    }
  }
}

export const messagesAPI = new MessagesAPI();
