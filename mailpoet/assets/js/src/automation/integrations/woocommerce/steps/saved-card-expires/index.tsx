import { __ } from '@wordpress/i18n';

import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Customer's saved card expires" trigger
  __('card', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer's saved card expires" trigger
  __('credit card', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer's saved card expires" trigger
  __('payment', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer's saved card expires" trigger
  __('expiry', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer's saved card expires" trigger
  __('expiration', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer's saved card expires" trigger
  __('woocommerce', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce:saved-card-expires',
  group: 'triggers',
  // translators: automation trigger title
  title: () => __("Customer's saved card expires", 'mailpoet'),
  description: () =>
    __(
      "Triggers a set number of days before a customer's saved card expires.",
      'mailpoet',
    ),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_saved_card_expires',
      }}
    >
      {__(
        "The customer's saved card expires trigger is a premium feature.",
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
