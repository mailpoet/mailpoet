import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';

const keywords = [
  __('review', 'mailpoet'),
  __('buy', 'mailpoet'),
  __('comment', 'mailpoet'),
  __('ecommerce', 'mailpoet'),
  __('woocommerce', 'mailpoet'),
  __('product', 'mailpoet'),
  __('order', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:made-a-review',
  group: 'triggers',
  title: () => __('Customer makes a review', 'mailpoet'),
  description: () =>
    __('Start the automation when a customer makes a review.', 'mailpoet'),

  subtitle: () => __('Trigger', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_made_a_review',
      }}
    >
      {__(
        'Starting an automation by creating a review is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
