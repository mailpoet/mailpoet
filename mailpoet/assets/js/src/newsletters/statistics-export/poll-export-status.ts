import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';

export type ExportFormat = 'csv' | 'xlsx';

export type StatusPayload = {
  taskId: number;
  status: string;
  exportFileURL?: string;
  totalExported?: number;
  error?: string;
};

const POLL_INTERVAL_MS = 2500;
const POLL_TIMEOUT_MS = 5 * 60 * 1000;

export const fetchExportStatus = (taskId: number) =>
  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'statisticsExport',
    action: 'getStatus',
    data: { task_id: taskId },
  }).then((response) => response.data as StatusPayload);

type PollOptions = {
  onComplete: (status: StatusPayload) => void;
  onError: (message: string) => void;
};

export function pollExportStatus(
  taskId: number,
  options: PollOptions,
): { cancel: () => void } {
  let timer: number | null = null;
  let cancelled = false;
  const startedAt = Date.now();

  const cancel = () => {
    cancelled = true;
    if (timer !== null) {
      window.clearTimeout(timer);
      timer = null;
    }
  };

  const tick = () => {
    if (cancelled) {
      return;
    }
    if (Date.now() - startedAt > POLL_TIMEOUT_MS) {
      options.onError(
        __(
          'The export is taking longer than expected. Please try again later.',
          'mailpoet',
        ),
      );
      return;
    }

    fetchExportStatus(taskId)
      .then((status) => {
        if (cancelled) {
          return;
        }
        if (status.error) {
          options.onError(status.error);
          return;
        }
        if (status.exportFileURL) {
          options.onComplete(status);
          return;
        }
        timer = window.setTimeout(tick, POLL_INTERVAL_MS);
      })
      .catch((response: ErrorResponse) => {
        if (cancelled) {
          return;
        }
        const message =
          response?.errors?.[0]?.message ??
          __('Could not check the export status.', 'mailpoet');
        options.onError(message);
      });
  };

  tick();
  return { cancel };
}
