export type ListingQueryParams = {
  page?: number;
  per_page?: number;
  offset?: number;
  limit?: number;
  orderby?: string;
  order?: 'asc' | 'desc';
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  search?: string;
  group?: string;
  filter?: Record<string, unknown>;
};

export type ListingMeta = {
  count: number;
  pages: number;
};

export type ListingGroup = {
  name: string;
  label: string;
  count: number;
};

export type ListingResponse<T> = {
  items: T[];
  meta: ListingMeta;
  filters?: Array<Record<string, unknown>>;
  groups?: ListingGroup[];
};

export type ListingEnvelope<T> = {
  data: ListingResponse<T>;
};
