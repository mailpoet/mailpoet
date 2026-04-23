export type Tag = {
  id: number;
  name: string;
  description: string;
  subscribers_count: number;
  created_at: string | null;
  updated_at: string | null;
};

export type TagListMeta = {
  count: number;
  pages: number;
};

export type ApiErrorResponse = {
  code: string;
  message: string;
  data?: {
    status?: number;
    errors?: Record<string, string>;
  };
};
