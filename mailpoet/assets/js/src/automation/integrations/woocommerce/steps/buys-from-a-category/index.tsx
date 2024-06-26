import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Customer buys from a category" trigger
  __('category', 'mailpoet'),
  // translators: verb, used as a search keyword for "Customer buys from a category" trigger
  __('buy', 'mailpoet'),
  // translators: verb, used as a search keyword for "Customer buys from a category" trigger
  __('purchase', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a category" trigger
  __('ecommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a category" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a category" trigger
  __('product', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a category" trigger
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
