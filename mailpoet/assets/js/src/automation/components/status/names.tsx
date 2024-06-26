import { __ } from '@wordpress/i18n';
import { AutomationStatus } from '../../listing/automation';

type NamesProps = {
  [key: string]: string;
};

export const automationStatusNames: NamesProps = {
  [AutomationStatus.ACTIVE]: __('Active', 'mailpoet'),
  [AutomationStatus.DEACTIVATING]: __('Deactivating', 'mailpoet'),
  [AutomationStatus.DRAFT]: __('Draft', 'mailpoet'),
  [AutomationStatus.TRASH]: __('In trash', 'mailpoet'),
};

// Make sure this translation map is in sync with the backend in SubscriberStatistics
export const automationRunStatusNames: NamesProps = {
  running: __('In Progress', 'mailpoet'),
  cancelled: __('Cancelled', 'mailpoet'),
  complete: __('Completed', 'mailpoet'),
  failed: __('Failed', 'mailpoet'),
};
