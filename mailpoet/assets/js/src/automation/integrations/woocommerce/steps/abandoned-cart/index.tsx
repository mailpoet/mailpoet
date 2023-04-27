import { __ } from '@wordpress/i18n';
import { Icon } from './icon';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';

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
  title: () => __('Subscriber abandons cart', 'mailpoet-premium'),
  description: () =>
    __(
      'Start the automation when a subscriber who has items in the shopping cart leaves your website without checking out.',
      'mailpoet-premium',
    ),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  icon: () => <Icon />,
  edit: () => null,
  foreground: '#2271b1',
  background: '#f0f6fc',
  createStep: (stepData) => stepData,
} as const;
