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
  key: 'mailpoet:abandoned-cart',
  group: 'triggers',
  title: () => __('Subscriber abandons cart', 'mailpoet-premium'),
  description: () =>
    __(
      'Start the automation when logged-in subscribers who have items in their shopping carts leave your website for more than 30 minutes without checking out.',
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
