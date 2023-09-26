import { __ } from '@wordpress/i18n';
import { Edit } from './edit';
import { Icon } from './icon';
import { StepType } from '../../../../editor/store';

const keywords = [
  'cart',
  'abandoned',
  'abandon',
  'abandonment',
  'abandoned cart',
  'abandon cart',
];
export const step: StepType = {
  key: 'woocommerce:abandoned-cart',
  group: 'triggers',
  title: () => __('Subscriber abandons cart', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when a subscriber who has items in the shopping cart leaves your website without checking out.',
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
