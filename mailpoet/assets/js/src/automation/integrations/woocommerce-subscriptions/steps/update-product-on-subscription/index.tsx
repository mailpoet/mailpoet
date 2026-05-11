import { __, _x } from '@wordpress/i18n';
import { pencil } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('subscription', 'mailpoet'),
  __('product', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:update-product-on-subscription',
  group: 'actions',
  title: () => __('Update product on subscription', 'mailpoet'),
  description: () => __('Update a product line on a subscription.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => pencil,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_update_product_on_subscription',
      }}
    >
      {__(
        'Updating products on subscriptions is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
