import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';

const keywords = [
  // translators: noun, used as a search keyword for "Customer makes a review" trigger
  __('review', 'mailpoet'),
  // translators: verb, used as a search keyword for "Customer makes a review" trigger
  __('buy', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer makes a review" trigger
  __('comment', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer makes a review" trigger
  __('ecommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer makes a review" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer makes a review" trigger
  __('product', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer makes a review" trigger
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
