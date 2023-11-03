import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('subscription', 'mailpoet'),
  __('expired', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:subscription-expired',
  group: 'triggers',
  title: () => __('Woo Subscription expired', 'mailpoet'),
  description: () =>
    __('Start the automation when a subscription expires.', 'mailpoet'),

  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_woo_subscription_expired',
      }}
    >
      {__(
        'Starting an automation when a subscription expires is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
