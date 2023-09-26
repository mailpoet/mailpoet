import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { Edit } from './edit';

const keywords = [
  __('ecommerce', 'mailpoet'),
  __('product', 'mailpoet'),
  __('order', 'mailpoet'),
  __('buy', 'mailpoet'),
  __('bought', 'mailpoet'),
  __('woocommerce', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:buys-a-product',
  group: 'triggers',
  title: () => __('Customer buys a product', 'mailpoet'),
  description: () =>
    __('Start the automation when a customer buys a product.', 'mailpoet'),

  subtitle: () => __('Trigger', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => <Edit />,
} as const;
