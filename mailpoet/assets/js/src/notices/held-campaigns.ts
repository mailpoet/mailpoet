import { __, _n, sprintf } from '@wordpress/i18n';

export const VISIBLE_ROWS_LIMIT = 5;

const MINUTE = 60;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

type Lateness = { unit: 'minute' | 'hour' | 'day'; count: number };
export type HeldRowAction = 'resume' | 'reschedule' | 'cancel';
export type HeldConfirmAction = 'cancel' | 'reschedule';

export function getLateness(lateSeconds: number): Lateness {
  if (lateSeconds >= 2 * DAY) {
    return { unit: 'day', count: Math.floor(lateSeconds / DAY) };
  }
  if (lateSeconds >= HOUR) {
    return { unit: 'hour', count: Math.floor(lateSeconds / HOUR) };
  }
  return {
    unit: 'minute',
    count: Math.max(1, Math.floor(lateSeconds / MINUTE)),
  };
}

export function lateText(lateSeconds: number): string {
  const { unit, count } = getLateness(lateSeconds);
  if (unit === 'day') {
    return sprintf(_n('%d day late', '%d days late', count, 'mailpoet'), count);
  }
  if (unit === 'hour') {
    return sprintf(
      _n('%d hour late', '%d hours late', count, 'mailpoet'),
      count,
    );
  }
  return sprintf(
    _n('%d minute late', '%d minutes late', count, 'mailpoet'),
    count,
  );
}

export function scheduleText(
  row: HeldCampaign,
  formatDate: (date: string) => string,
): string {
  const late = lateText(row.late_seconds);
  if (row.scheduled_at === null) {
    return sprintf(__('Queued · %s', 'mailpoet'), late);
  }
  return sprintf(
    __('Scheduled for %1$s · %2$s', 'mailpoet'),
    formatDate(row.scheduled_at),
    late,
  );
}

export function removeRow(rows: HeldCampaign[], id: number): HeldCampaign[] {
  return rows.filter((row) => row.id !== id);
}

export function visibleRows(
  rows: HeldCampaign[],
  showAll: boolean,
): HeldCampaign[] {
  return showAll ? rows : rows.slice(0, VISIBLE_ROWS_LIMIT);
}

/**
 * Index (in the visible rows after removal) of the row to focus, or -1 for
 * the heading. A collapsed list with hidden rows keeps its length, because
 * the next hidden row slides in.
 */
export function nextFocusIndex(
  visible: HeldCampaign[],
  removedIndex: number,
  totalCount: number = visible.length,
): number {
  const remainingVisible =
    totalCount > visible.length ? visible.length : visible.length - 1;
  return removedIndex < remainingVisible ? removedIndex : -1;
}

export function rowActions(row?: HeldCampaign): HeldRowAction[] {
  if (!row) return [];
  return row.can_reschedule && row.can_edit_link
    ? ['resume', 'reschedule', 'cancel']
    : ['resume', 'cancel'];
}

export type BusyRows = Record<number, HeldRowAction>;

export function markBusy(
  busy: BusyRows,
  id: number,
  action: HeldRowAction,
): BusyRows {
  return { ...busy, [id]: action };
}

export function clearBusy(busy: BusyRows, id: number): BusyRows {
  const { [id]: removed, ...rest } = busy;
  return rest;
}

export function subjectText(subject: string): string {
  return subject || __('(no subject)', 'mailpoet');
}

export function confirmCopy(
  type: HeldCampaign['type'],
  action: HeldConfirmAction,
): string {
  if (action === 'reschedule') {
    return __(
      'The email goes back to drafts so you can pick a new time.',
      'mailpoet',
    );
  }
  if (type === 'notification_history') {
    return __(
      'This issue will be deleted. Its posts go in the next one.',
      'mailpoet',
    );
  }
  if (type === 're_engagement') {
    return __('This run is skipped.', 'mailpoet');
  }
  return __('This email returns to drafts.', 'mailpoet');
}
