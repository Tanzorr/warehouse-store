import { User } from '../../models/user';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  authToken: string;
  loggedUser: User;
}
