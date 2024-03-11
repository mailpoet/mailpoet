import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { Edit } from './edit';

const keywords = [
  // translators: noun, used as a search keyword for "Customer buys a product" trigger
  __('ecommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys a product" trigger
  __('product', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys a product" trigger
  __('order', 'mailpoet'),
  // translators: verb, used as a search keyword for "Customer buys a product" trigger
  __('buy', 'mailpoet'),
  // translators: verb past tense, used as a search keyword for "Customer buys a product" trigger
  __('bought', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys a product" trigger
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
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.4' }}>
      <Icon />
    </div>
  ),
  edit: () => <Edit />,
} as const;
