import { __, _x } from '@wordpress/i18n';
import { commentContent } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';

const keywords = [
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order note added" trigger
  __('order', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order note added" trigger
  __('note', 'mailpoet'),
  // translators: noun, used as a search keyword for "Order note added" trigger
  __('comment', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce:order-note-added',
  group: 'triggers',
  title: () => __('Order note added', 'mailpoet'),
  description: () =>
    __(
      'Fires when any note is added to an order, can include both private notes and notes to the customer. These notes appear on the right of the order edit screen.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => commentContent,
  edit: () => <Edit />,
} as const;
