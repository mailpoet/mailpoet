import { AutomationStatus } from '../automation';
import type { AutomationItem } from '../store/types';
import type {
  MailPoetSegment,
  ManualStartErrorResponse,
  ManualStartErrorState,
  ManualStartMetadata,
  ManualStartPreview,
  ManualStartSegmentOption,
} from './types';

export function normalizeSegmentId(
  id: string | number | null | undefined,
): string {
  const value = String(id ?? '').trim();
  if (!value) return '';

  const numericValue = Number(value);
  return Number.isInteger(numericValue) && numericValue > 0
    ? String(numericValue)
    : '';
}

export function getSegmentIdNumber(
  id: string | number | null | undefined,
): number | null {
  const normalizedId = normalizeSegmentId(id);
  return normalizedId ? Number(normalizedId) : null;
}

export function isManualStartSupported(automation: AutomationItem): boolean {
  return (
    !automation.isLegacy &&
    automation.status === AutomationStatus.ACTIVE &&
    automation.manual_start?.supported === true
  );
}

function getAllowedSegmentIds(
  metadata?: ManualStartMetadata,
): Set<string> | null {
  if (!metadata?.segment_ids || metadata.segment_ids.length === 0) {
    return null;
  }

  const ids = metadata.segment_ids
    .map((id) => normalizeSegmentId(id))
    .filter(Boolean);
  return ids.length > 0 ? new Set(ids) : null;
}

function isAvailableSegment(segment: MailPoetSegment): boolean {
  return !segment.deleted_at && !!normalizeSegmentId(segment.id);
}

export function getManualStartDefaultListOptions(
  segments: MailPoetSegment[],
  metadata?: ManualStartMetadata,
): ManualStartSegmentOption[] {
  if (!metadata?.supported) {
    return [];
  }

  const allowedIds = getAllowedSegmentIds(metadata);

  return segments.reduce<ManualStartSegmentOption[]>((options, segment) => {
    const id = normalizeSegmentId(segment.id);
    if (
      segment.type === 'default' &&
      isAvailableSegment(segment) &&
      (!allowedIds || allowedIds.has(id))
    ) {
      options.push({
        id,
        name: segment.name,
        subscribers: segment.subscribers,
      });
    }
    return options;
  }, []);
}

export function getManualStartDynamicSegmentOptions(
  segments: MailPoetSegment[],
): ManualStartSegmentOption[] {
  return segments.reduce<ManualStartSegmentOption[]>((options, segment) => {
    const id = normalizeSegmentId(segment.id);
    if (segment.type === 'dynamic' && isAvailableSegment(segment)) {
      options.push({
        id,
        name: segment.name,
        subscribers: segment.subscribers,
      });
    }
    return options;
  }, []);
}

export function previewMatchesSelections(
  preview: ManualStartPreview | null | undefined,
  segmentId: string | number | null | undefined,
  filterSegmentId: string | number | null | undefined,
): boolean {
  if (!preview) {
    return false;
  }

  const selectedSegmentId = getSegmentIdNumber(segmentId);
  if (!selectedSegmentId) {
    return false;
  }

  return (
    preview.segment_id === selectedSegmentId &&
    (preview.filter_segment_id ?? null) === getSegmentIdNumber(filterSegmentId)
  );
}

export function canConfirmManualStart(
  preview: ManualStartPreview | null | undefined,
  segmentId: string | number | null | undefined,
  filterSegmentId: string | number | null | undefined,
): boolean {
  return (
    previewMatchesSelections(preview, segmentId, filterSegmentId) &&
    preview.eligible_count > 0 &&
    !preview.duplicate_in_progress
  );
}

function isObject(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

function getNumber(value: unknown): number | null {
  return typeof value === 'number' && Number.isFinite(value) ? value : null;
}

function getStringArray(value: unknown): string[] {
  return Array.isArray(value)
    ? value.filter((item): item is string => typeof item === 'string')
    : [];
}

function getNumberRecord(value: unknown): Record<string, number> {
  if (!isObject(value)) {
    return {};
  }

  const record: Record<string, number> = {};
  Object.entries(value).forEach(([key, count]) => {
    const numericCount = getNumber(count);
    if (numericCount !== null) {
      record[key] = numericCount;
    }
  });
  return record;
}

function getStringRecord(value: unknown): Record<string, string> | undefined {
  if (!isObject(value)) {
    return undefined;
  }

  const record: Record<string, string> = {};
  Object.entries(value).forEach(([key, item]) => {
    if (typeof item === 'string') {
      record[key] = item;
    }
  });

  return Object.keys(record).length > 0 ? record : undefined;
}

function normalizePreview(value: unknown): ManualStartPreview | undefined {
  if (!isObject(value)) {
    return undefined;
  }

  const automationId = getNumber(value.automation_id);
  const segmentId = getNumber(value.segment_id);
  const selectedCount = getNumber(value.selected_count);
  const eligibleCount = getNumber(value.eligible_count);
  if (
    typeof value.preview_signature !== 'string' ||
    automationId === null ||
    segmentId === null ||
    selectedCount === null ||
    eligibleCount === null ||
    typeof value.duplicate_in_progress !== 'boolean'
  ) {
    return undefined;
  }

  const filterSegmentId =
    value.filter_segment_id === null
      ? null
      : getNumber(value.filter_segment_id);
  if (filterSegmentId === null && value.filter_segment_id !== null) {
    return undefined;
  }

  return {
    preview_signature: value.preview_signature,
    automation_id: automationId,
    segment_id: segmentId,
    filter_segment_id: filterSegmentId,
    selected_count: selectedCount,
    eligible_count: eligibleCount,
    skipped_by_reason: getNumberRecord(value.skipped_by_reason),
    deferred_reason_keys: getStringArray(value.deferred_reason_keys),
    duplicate_in_progress: value.duplicate_in_progress,
  };
}

function normalizeErrorData(data: unknown): ManualStartErrorResponse['data'] {
  if (!isObject(data)) {
    return {};
  }

  const normalizedData: ManualStartErrorResponse['data'] = {};
  const status = getNumber(data.status);
  if (status !== null) {
    normalizedData.status = status;
  }

  const preview = normalizePreview(data.preview);
  if (preview) {
    normalizedData.preview = preview;
  }

  if (Array.isArray(data.errors) || isObject(data.errors)) {
    normalizedData.errors = data.errors;
  }
  if (data.details !== undefined) {
    normalizedData.details = data.details;
  }
  const params = getStringRecord(data.params);
  if (params) {
    normalizedData.params = params;
  }

  return normalizedData;
}

export function normalizeManualStartError(
  error: unknown,
): ManualStartErrorResponse {
  if (isObject(error)) {
    return {
      code:
        typeof error.code === 'string'
          ? error.code
          : 'manual_start_unknown_error',
      message: typeof error.message === 'string' ? error.message : '',
      data: normalizeErrorData(error.data),
    };
  }

  return {
    code: 'manual_start_unknown_error',
    message: '',
    data: {},
  };
}

export function getManualStartErrorState(
  error: ManualStartErrorResponse | null | undefined,
): ManualStartErrorState | null {
  if (!error) {
    return null;
  }

  if (error.code === 'manual_start_zero_eligible') {
    return 'zero-eligible';
  }
  if (error.code === 'manual_start_in_progress') {
    return 'duplicate-in-progress';
  }
  if (error.code === 'manual_start_stale_preview') {
    return 'stale-preview';
  }
  if (
    typeof error.data.status === 'number' &&
    error.data.status >= 400 &&
    error.data.status < 500
  ) {
    return 'validation';
  }

  return 'unknown';
}

export function isBlockingManualStartError(
  error: ManualStartErrorResponse | null | undefined,
): boolean {
  return getManualStartErrorState(error) !== null;
}
