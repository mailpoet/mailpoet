import { __, _x } from '@wordpress/i18n';
import { cart } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('order', 'mailpoet'),
  __('paid', 'mailpoet'),
  __('payment', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce:order-paid',
  group: 'triggers',
  title: () => __('Order paid', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when payment for an order is completed.',
      'mailpoet',
    ),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => cart,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_order_paid',
      }}
    >
      {__(
        'Starting an automation when an order is paid is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
