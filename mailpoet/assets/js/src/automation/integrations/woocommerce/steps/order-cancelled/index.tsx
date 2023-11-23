import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Order cancelled" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order cancelled" trigger
  __('order', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Order cancelled" trigger
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
