import { __, _x } from '@wordpress/i18n';
import { commentContent } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('order', 'mailpoet'),
  __('note', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce:add-order-note',
  group: 'actions',
  title: () => __('Add order note', 'mailpoet'),
  description: () =>
    __('Add a private or customer note to an order.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => commentContent,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_add_order_note',
      }}
    >
      {__('Adding an order note is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
