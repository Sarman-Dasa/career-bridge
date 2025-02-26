export interface User {
  id: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  profile_image?: string;
  is_online?: boolean;
  last_message?: {
    message?: string;
    created_at?: string;
    attachments?: any[];
    unread_messages?: number;
  };
}

export interface Message {
  id: string;
  sender_id: string;
  receiver_id?: string;
  group_id?: string;
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

export interface GroupMember {
  id: string;
  user_id: string;
  group_id: string;
  role: 'admin' | 'member';
  user?: User;
  created_at: string;
  updated_at: string;
}

export interface Group {
  id: string;
  name: string;
  description?: string;
  image?: string;
  created_by: string;
  members: GroupMember[];
  created_at: string;
  updated_at: string;
  last_message?: Message;
} 
