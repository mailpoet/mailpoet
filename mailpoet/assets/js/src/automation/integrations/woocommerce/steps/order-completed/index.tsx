import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Order completed" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order completed" trigger
  __('order', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Order completed" trigger
  __('completed', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:order-completed',
  group: 'triggers',
  title: () => __('Order completed', 'mailpoet'),
  description: () =>
    __('Start the automation when an order is completed.', 'mailpoet'),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.25' }}>
      <Icon />
    </div>
  ),
  edit: () => null,
} as const;
