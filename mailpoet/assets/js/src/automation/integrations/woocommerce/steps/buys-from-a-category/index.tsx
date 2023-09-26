import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';
import { Icon } from './icon';

const keywords = [
  __('category', 'mailpoet'),
  __('buy', 'mailpoet'),
  __('purchase', 'mailpoet'),
  __('ecommerce', 'mailpoet'),
  __('woocommerce', 'mailpoet'),
  __('product', 'mailpoet'),
  __('order', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:buys-from-a-category',
  group: 'triggers',
  title: () => __('Customer buys from a category', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when a customer buys a product from a category.',
      'mailpoet',
    ),

  subtitle: () => __('Trigger', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => <Edit />,
} as const;
