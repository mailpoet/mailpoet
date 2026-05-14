import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { ProductSubscriptionIcon } from '../product-subscription-icon';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('subscription', 'mailpoet'),
  __('status', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:change-subscription-status',
  group: 'actions',
  title: () => __('Change subscription status', 'mailpoet'),
  description: () => __('Change the status of a subscription.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => <ProductSubscriptionIcon type="status" />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_change_subscription_status',
      }}
    >
      {__('Changing a subscription status is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
