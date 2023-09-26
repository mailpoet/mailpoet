import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';
import { Icon } from './icon';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('order', 'mailpoet'),
  __('status', 'mailpoet'),
  __('change', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:order-status-changed',
  group: 'triggers',
  title: () => __('Order status changed', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when an order is created or changed to a specific status.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => <Edit />,
} as const;
