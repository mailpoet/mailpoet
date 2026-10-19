import { __, _n, sprintf } from '@wordpress/i18n';

export const VISIBLE_ROWS_LIMIT = 5;

const MINUTE = 60;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

type Lateness = { unit: 'minute' | 'hour' | 'day'; count: number };
export type HeldRowAction = 'resume' | 'reschedule' | 'cancel';
export type HeldBulkAction = 'resume' | 'cancel';
export type HeldBulkResult = {
  done: number[];
  failed: { id: number; message: string }[];
};

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

export function confirmCopy(type: HeldCampaign['type']): string {
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

export function actionLabel(action: HeldRowAction): string {
  if (action === 'resume') return __('Send now', 'mailpoet');
  if (action === 'reschedule') return __('Reschedule…', 'mailpoet');
  return __('Don’t send…', 'mailpoet');
}

export function confirmButtonLabel(): string {
  return __('Don’t send', 'mailpoet');
}

export function actionScreenReaderText(
  action: HeldRowAction,
  subject: string,
): string {
  if (action === 'resume') {
    return sprintf(__('Send now: %s', 'mailpoet'), subject);
  }
  if (action === 'reschedule') {
    return sprintf(__('Reschedule: %s', 'mailpoet'), subject);
  }
  return sprintf(__('Don’t send: %s', 'mailpoet'), subject);
}

export function unsentAnnouncement(subject: string): string {
  return sprintf(__('Won’t be sent: %s', 'mailpoet'), subject);
}

export function showBulkActions(count: number): boolean {
  return count >= 2;
}

export function bulkResumeMessage(count: number): string {
  return sprintf(
    _n(
      '%d email will be sent to its lists now.',
      '%d emails will be sent to their lists now.',
      count,
      'mailpoet',
    ),
    count,
  );
}

export function bulkCancelCopy(types: HeldCampaign['type'][]): string {
  const sentences: string[] = [];
  if (types.includes('standard')) {
    sentences.push(__('Newsletters go back to drafts.', 'mailpoet'));
  }
  if (types.includes('notification_history')) {
    sentences.push(
      __(
        'Post notification issues are deleted and their posts go in the next one.',
        'mailpoet',
      ),
    );
  }
  if (types.includes('re_engagement')) {
    sentences.push(__('Re-engagement runs are skipped.', 'mailpoet'));
  }
  return sentences.join(' ');
}

export function bulkScreenReaderText(action: HeldBulkAction): string {
  return action === 'resume'
    ? __('Send all held emails', 'mailpoet')
    : __('Don’t send any held email', 'mailpoet');
}

export function applyBulkResult(
  rows: HeldCampaign[],
  result: HeldBulkResult,
): HeldCampaign[] {
  return rows.filter((row) => !result.done.includes(row.id));
}

export function bulkSummary(
  action: HeldBulkAction,
  doneCount: number,
  failedCount: number,
): string {
  const parts: string[] = [];
  if (doneCount > 0) {
    parts.push(
      action === 'resume'
        ? sprintf(
            _n('%d email sent', '%d emails sent', doneCount, 'mailpoet'),
            doneCount,
          )
        : sprintf(
            _n(
              '%d email won’t be sent',
              '%d emails won’t be sent',
              doneCount,
              'mailpoet',
            ),
            doneCount,
          ),
    );
  }
  if (failedCount > 0) {
    parts.push(
      sprintf(
        action === 'resume'
          ? __('%d couldn’t be sent', 'mailpoet')
          : __('%d couldn’t be updated', 'mailpoet'),
        failedCount,
      ),
    );
  }
  return parts.join(', ');
}
