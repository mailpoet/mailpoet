import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('order', 'mailpoet'),
  __('cancelled', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:order-cancelled',
  group: 'triggers',
  title: () => __('Order cancelled', 'mailpoet'),
  description: () =>
    __('Start the automation when an order is cancelled.', 'mailpoet'),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => null,
} as const;
