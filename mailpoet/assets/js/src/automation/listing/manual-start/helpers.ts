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

export function normalizeManualStartError(
  error: unknown,
): ManualStartErrorResponse {
  if (isObject(error)) {
    const data = isObject(error.data) ? error.data : {};
    return {
      code:
        typeof error.code === 'string'
          ? error.code
          : 'manual_start_unknown_error',
      message: typeof error.message === 'string' ? error.message : '',
      data,
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
