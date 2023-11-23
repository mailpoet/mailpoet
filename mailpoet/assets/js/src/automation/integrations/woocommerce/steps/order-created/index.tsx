import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Order created" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order created" trigger
  __('order', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Order created" trigger
  __('new', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Order created" trigger
  __('created', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:order-created',
  group: 'triggers',
  title: () => __('Order created', 'mailpoet'),
  description: () =>
    __('Start the automation when an order is created.', 'mailpoet'),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => null,
} as const;
