import {Media} from './media';


export interface User {
  id: string;
  name: string;
  email: string;
  password: string;
  password_confirmation?: string;
  email_verified_at?: string;
  created_at?: string;
  updated_at?: string;
  ownVaults?: any[];
  sharedVaults?: any[];
  shared_access_id?: any['id'] | undefined;
  pivot?: Pivot;
  role?: UserRole;
  media?: Media[] | undefined;
}

export interface CreateUserResponse {
  message: string;
  user: User;
}

type Pivot = {
  user_id: number;
  accessible_id: number;
};

export type UserRole = 'admin' | 'user';
