export interface User {
  id: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  profile_image?: string;
  is_online?: boolean;
  with_last_message?: {
    message?: string;
    created_at?: string;
    attachments?: any[];
    unread_messages?: number;
  };
}

export interface Message {
  id: string;
  sender_id: string;
  receiver_id: string;
  message?: string;
  is_sent?: boolean;
  is_delivered?: boolean;
  is_seen?: boolean;
  is_edited?: boolean;
  delivered_at?: string;
  seen_at?: string;
  created_at: string;
  timeAgo?: string;
  attachments?: Array<{
    id: string;
    file_path: string;
    file_name: string;
    is_audio_file?: boolean;
  }>;
} 
