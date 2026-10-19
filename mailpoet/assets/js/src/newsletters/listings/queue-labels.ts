import { __ } from '@wordpress/i18n';

export function getQueueActionLabels(queue: {
  status: string | null;
  pause_reason?: string | null;
}) {
  const isHeld = queue.status === 'paused' && queue.pause_reason === 'overdue';
  return {
    isHeld,
    showPause: !isHeld,
    resumeLabel: isHeld ? __('Send now', 'mailpoet') : __('Resume', 'mailpoet'),
  };
}

// Immediate sends also store scheduled_at, so only a scheduled queue may
// show the scheduled countdown.
export function getScheduledFor(newsletter: {
  sent_at: string | null;
  queue: { status?: unknown; scheduled_at: string | null };
}): string | null {
  return (
    newsletter.sent_at ||
    (newsletter.queue.status === 'scheduled'
      ? newsletter.queue.scheduled_at
      : null)
  );
}
