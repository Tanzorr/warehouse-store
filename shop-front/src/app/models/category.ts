import {PageLinks} from './pagination';

export interface Category {
  id: number;
  name: string;
  description: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface CategoryResponse {
  currentPage: number;
  data: Category[];
  firstPageUrl: string;
  from: number;
  lastPage: number;
  lastPageUrl: string;
  links: PageLinks[];
  total: number;
  to: number;
  error: string | null;
  perPage: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}
