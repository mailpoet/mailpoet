import { __, _x } from '@wordpress/i18n';
import { close } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('subscription', 'mailpoet'),
  __('product', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:remove-product-from-subscription',
  group: 'actions',
  title: () => __('Remove product from subscription', 'mailpoet'),
  description: () => __('Remove a product from a subscription.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => close,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign:
          'create_automation_editor_remove_product_from_subscription',
      }}
    >
      {__(
        'Removing products from subscriptions is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
