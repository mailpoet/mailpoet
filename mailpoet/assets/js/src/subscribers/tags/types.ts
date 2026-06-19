export type Tag = {
  id: number;
  name: string;
  description: string;
  subscribers_count: number;
  created_at: string | null;
  updated_at: string | null;
};

export type TagsRequestFilter = {
  from?: string;
  to?: string;
  subscribers?: string[];
};

export type SubscriberCountBucket = {
  value: string;
  min: number;
  max: number | null;
};

export type TagListMeta = {
  count: number;
  pages: number;
  subscriber_count_buckets: SubscriberCountBucket[];
};

export type ApiErrorResponse = {
  code: string;
  message: string;
  data?: {
    status?: number;
    errors?: Record<string, string>;
  };
};
