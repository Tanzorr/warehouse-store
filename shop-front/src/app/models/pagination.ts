export interface PageLinks {
  url: string | null;
  label: string;
  active: boolean;
}

export interface PageQueryParams {
  page?: string;
  perPage?: string;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  search?: string | null;
  categoryId?: number;
  minPrice?: string;
  maxPrice?: string;
  paginationLinkUrl?: string;
  category_ids?: number[]; // Optional property to hold selected category IDs
}
