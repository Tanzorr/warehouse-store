import {PageLinks} from './pagination';

export interface Product {
  id: number;
  name: string;
  description: string;
  sale_price: number;
  stock: number;
  categoryId: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface ProductsResponse {
  currentPage: number;
  data: Product[];
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

