import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Woo Subscription expired" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription expired" trigger
  __('subscription', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Subscription expired" trigger
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
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.3' }}>
      <Icon />
    </div>
  ),
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
