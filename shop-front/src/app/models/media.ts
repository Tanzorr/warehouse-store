import { User } from './user';
export interface Media {
  id: string;
  user_id: User['id'];
  file_path: string;
  file_name: string;
}

export interface PaginatedMediasResponse {
  current_page: number;
  data: Media[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: PaginationLink[];
  next_page_url: string | null;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}
