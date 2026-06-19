export type CustomField = {
  id: number;
  name: string;
  label: string;
  type: string;
  params: Record<string, unknown>;
  required: boolean;
  subscribers_count: number;
  forms_count: number;
  dynamic_segments_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type CustomFieldType =
  | 'text'
  | 'textarea'
  | 'radio'
  | 'checkbox'
  | 'select'
  | 'date';

export type CustomFieldPayload = {
  name: string;
  type: CustomFieldType;
  params: Record<string, unknown>;
};

export type CustomFieldDateSettings = {
  dateTypes: Array<{ label: string; value: string }>;
  dateFormats: Record<string, string[]>;
};

export type CustomFieldsRequestFilter = {
  type?: string[];
};

export type CustomFieldListMeta = {
  count: number;
  pages: number;
};

export type CustomFieldListGroup = {
  name: 'all' | 'trash';
  label: string;
  count: number;
};

export type ApiErrorResponse = {
  code: string;
  message: string;
  data?: {
    status?: number;
  };
};
