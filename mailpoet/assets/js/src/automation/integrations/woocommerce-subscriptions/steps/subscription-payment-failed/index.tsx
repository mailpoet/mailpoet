import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';

const keywords = [
  // translators: noun, used as a search keyword for "Woo Subscription payment failed" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription payment failed" trigger
  __('subscription', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription payment failed" trigger
  __('payment', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Subscription payment failed" trigger
  __('failed', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce-subscriptions:subscription-payment-failed',
  group: 'triggers',
  title: () => __('Woo Subscription payment failed', 'mailpoet'),
  description: () =>
    __('Start the automation when a payment failed.', 'mailpoet'),

  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_woo_subscription_renewed',
      }}
    >
      {__(
        'Starting an automation when a subscription payment fails is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
