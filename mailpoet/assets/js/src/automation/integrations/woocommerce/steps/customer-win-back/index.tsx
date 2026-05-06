import { __, _x } from '@wordpress/i18n';
import { people } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('woocommerce', 'mailpoet'),
  __('customer', 'mailpoet'),
  __('win back', 'mailpoet'),
  __('inactive', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce:customer-win-back',
  group: 'triggers',
  title: () => __('Customer win back', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when a customer has not purchased recently.',
      'mailpoet',
    ),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => people,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_customer_win_back',
      }}
    >
      {__(
        'Starting a customer win-back automation is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
