import { __, _x } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('subscription', 'mailpoet'),
  __('product', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-subscriptions:add-product-to-subscription',
  group: 'actions',
  title: () => __('Add product to subscription', 'mailpoet'),
  description: () => __('Add a product to a subscription.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => plus,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_add_product_to_subscription',
      }}
    >
      {__('Adding products to subscriptions is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
