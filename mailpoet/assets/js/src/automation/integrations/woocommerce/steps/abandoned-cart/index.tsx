import { __ } from '@wordpress/i18n';
import { Edit } from './edit';
import { Icon } from './icon';
import { StepType } from '../../../../editor/store';

const keywords = [
  // translators: noun, used as a search keyword for "User abandons cart" trigger
  'cart',
  // translators: adjective, used as a search keyword for "User abandons cart" trigger
  'abandoned',
  // translators: verb, used as a search keyword for "User abandons cart" trigger
  'abandon',
  // translators: noun, used as a search keyword for "User abandons cart" trigger
  'abandonment',
  // translators: used as a search keyword for "User abandons cart" trigger
  'abandoned cart',
  // translators: used as a search keyword for "User abandons cart" trigger
  'abandon cart',
];
export const step: StepType = {
  key: 'woocommerce:abandoned-cart',
  group: 'triggers',
  title: () => __('User abandons cart', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when a user who has items in the shopping cart leaves your website without checking out.',
      'mailpoet',
    ),
  subtitle: (): JSX.Element | string => __('Trigger', 'mailpoet'),
  keywords,
  icon: () => <Icon />,
  edit: () => <Edit />,
  foreground: '#2271b1',
  background: '#f0f6fc',
  createStep: (stepData) => stepData,
} as const;
