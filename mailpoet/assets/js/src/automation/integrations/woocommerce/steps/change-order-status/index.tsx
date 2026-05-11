import { __, _x } from '@wordpress/i18n';
import { update } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('order', 'mailpoet'),
  __('status', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce:change-order-status',
  group: 'actions',
  title: () => __('Change order status', 'mailpoet'),
  description: () => __('Change the status of an order.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => update,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_change_order_status',
      }}
    >
      {__('Changing an order status is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
