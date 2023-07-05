import { __ } from '@wordpress/i18n';

// Make sure this translation map is in sync with the backend in SubscriberStatistics
export const statusMap = {
  running: __('In Progress', 'mailpoet'),
  cancelled: __('Cancelled', 'mailpoet'),
  complete: __('Completed', 'mailpoet'),
  failed: __('Failed', 'mailpoet'),
};

export function StatusCell({ status }: { status: string }): JSX.Element {
  if (!Object.keys(statusMap).includes(status)) {
    return <>{status}</>;
  }
  return <>{statusMap[status]}</>;
}
