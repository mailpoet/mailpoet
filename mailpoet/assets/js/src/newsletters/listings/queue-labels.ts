import { __ } from '@wordpress/i18n';

type QueueState = {
  status: string | null;
  pause_reason?: string | null;
};

export function isHeldQueue(queue: QueueState): boolean {
  return queue.status === 'paused' && queue.pause_reason === 'overdue';
}

export function getQueueActionLabels(
  queue: QueueState & { resumed?: boolean },
) {
  const isHeld = !queue.resumed && isHeldQueue(queue);
  return {
    isHeld,
    resumeLabel: isHeld ? __('Send now', 'mailpoet') : __('Resume', 'mailpoet'),
  };
}

// Immediate sends also store scheduled_at, so a running queue must not show
// it as a scheduled countdown. Scheduled and paused queues keep their date.
export function getScheduledFor(newsletter: {
  sent_at: string | null;
  queue: { status?: unknown; scheduled_at: string | null };
}): string | null {
  return (
    newsletter.sent_at ||
    (newsletter.queue.status === null ||
    newsletter.queue.status === undefined ||
    newsletter.queue.status === 'running'
      ? null
      : newsletter.queue.scheduled_at)
  );
}
