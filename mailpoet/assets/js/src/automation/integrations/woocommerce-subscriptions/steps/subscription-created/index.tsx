import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Woo Subscription started" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription started" trigger
  __('subscriptions', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Subscription started" trigger
  __('new', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Subscription started" trigger
  __('created', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Subscription started" trigger
  __('started', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:subscription-created',
  group: 'triggers',
  title: () => __('Woo Subscription started', 'mailpoet'),
  description: () =>
    __('Start the automation when a subscription is created.', 'mailpoet'),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_subscription_created',
      }}
    >
      {__(
        'Starting an automation when a subscription is created is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
