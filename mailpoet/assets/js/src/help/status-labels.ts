const CRON_STATUS_ACTIVE = 'active';
const CRON_STATUS_INACTIVE = 'inactive';
const QUEUE_STATUS_PAUSED = 'paused';

/**
 * The queue has only two states: `paused`, or no status at all, which means it is
 * running. Anything else would be a state we do not know how to describe.
 */
export function getQueueStatusLabelKey(status?: string | null): string {
  return status === QUEUE_STATUS_PAUSED ? 'paused' : 'running';
}

/**
 * A missing daemon record means the task scheduler has never run, which is a
 * different thing from a daemon we cannot read the status of.
 */
export function getCronStatusLabelKey(status?: string | null): string {
  if (status === CRON_STATUS_ACTIVE) {
    return 'running';
  }

  if (status === CRON_STATUS_INACTIVE) {
    return 'cronWaiting';
  }

  return 'cronNeverStarted';
}
