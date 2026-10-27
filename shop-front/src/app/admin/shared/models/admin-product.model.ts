export interface AdminCategory {
  id: number;
  name: string;
  description: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface AdminProduct {
  id: number;
  category_id: number;
  name: string;
  description: string | null;
  purchase_price: number;
  sale_price: number;
  stock: number;
  sku: string;
  category?: AdminCategory;
  created_at?: string;
  updated_at?: string;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface AdminProductsResponse {
  data: AdminProduct[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: PaginationLink[];
}

export interface AdminCategoriesResponse {
  data: AdminCategory[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ProductPayload {
  category_id: number;
  name: string;
  description?: string | null;
  purchase_price: number;
  sale_price: number;
  stock: number;
  sku: string;
}

export interface CategoryPayload {
  name: string;
  description?: string | null;
}
