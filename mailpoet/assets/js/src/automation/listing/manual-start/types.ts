export const manualStartTriggerKey = 'mailpoet:someone-subscribes' as const;

export type ManualStartMetadata = {
  supported: boolean;
  trigger_key: typeof manualStartTriggerKey | null;
  segment_ids: number[] | null;
  disabled_reason?: string;
};

export type MailPoetSegment = {
  id: string | number;
  name: string;
  subscribers?: string | number;
  type: 'default' | 'wp_users' | 'woocommerce_users' | 'dynamic';
  deleted_at?: string | null;
};

export type ManualStartSegmentOption = {
  id: string;
  name: string;
  subscribers?: string | number;
};

export type ManualStartPreview = {
  preview_signature: string;
  automation_id: number;
  segment_id: number;
  filter_segment_id: number | null;
  selected_count: number;
  eligible_count: number;
  skipped_by_reason: Record<string, number>;
  deferred_reason_keys: string[];
  duplicate_in_progress: boolean;
};

export type ManualStartResult = {
  task_id: number;
  automation_id: number;
  segment_id: number;
  filter_segment_id: number | null;
  selected_count: number;
  eligible_count: number;
  queued_count: number;
  skipped_by_reason: Record<string, number>;
};

export type ManualStartPreviewRequest = {
  segment_id: number;
  filter_segment_id?: number | null;
};

export type ManualStartRequest = ManualStartPreviewRequest & {
  preview_signature: string;
};

export type ManualStartErrorResponse = {
  code: string;
  message: string;
  data: {
    status?: number;
    preview?: ManualStartPreview;
    errors?: Record<string, unknown> | unknown[];
    details?: unknown;
    params?: Record<string, string>;
  };
};

export type ManualStartErrorState =
  | 'zero-eligible'
  | 'duplicate-in-progress'
  | 'stale-preview'
  | 'validation'
  | 'unknown';
