import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Woo Subscription status changed" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription status changed" trigger
  __('subscriptions', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription status changed" trigger
  __('status', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Subscription status changed" trigger
  __('changed', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:subscription-status-changed',
  group: 'triggers',
  title: () => __('Woo Subscription status changed', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when subscription changed to a specific status.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_subscription_status_changed',
      }}
    >
      {__(
        'Starting an automation by changing the status of a subscription is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
