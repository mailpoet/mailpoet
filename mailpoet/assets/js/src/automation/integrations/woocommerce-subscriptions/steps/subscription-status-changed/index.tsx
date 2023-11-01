import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('subscriptions', 'mailpoet'),
  __('status', 'mailpoet'),
  __('change', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:subscription-status-changed',
  group: 'triggers',
  title: () => __('Woo subscription status changed', 'mailpoet'),
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
        'Starting an automation by changing the status of an subscription is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
