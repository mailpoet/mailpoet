import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Order status changed" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order status changed" trigger
  __('order', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order status changed" trigger
  __('status', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order status changed" trigger
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
