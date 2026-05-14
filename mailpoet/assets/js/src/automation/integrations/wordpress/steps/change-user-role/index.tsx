import { __, _x } from '@wordpress/i18n';
import { people } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('wordpress', 'mailpoet'),
  __('user', 'mailpoet'),
  __('role', 'mailpoet'),
  __('account', 'mailpoet'),
];

export const step: StepType = {
  key: 'wordpress:change-user-role',
  group: 'actions',
  title: () => __('Change user role', 'mailpoet'),
  description: () => __('Change the WordPress role for a user.', 'mailpoet'),
  subtitle: () => <LockedBadge text={_x('Premium', 'noun', 'mailpoet')} />,
  keywords,
  foreground: '#00a32a',
  background: '#edfaef',
  icon: () => people,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_change_user_role',
      }}
    >
      {__('Changing a user role is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
