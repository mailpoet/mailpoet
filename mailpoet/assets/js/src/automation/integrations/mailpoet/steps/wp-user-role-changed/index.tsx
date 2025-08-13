import { __ } from '@wordpress/i18n';
import { wordpress } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: used as a search keyword for "WordPress user role changes" automation trigger
  __('wordpress', 'mailpoet'),
  // translators: used as a search keyword for "WordPress user role changes" automation trigger
  __('user', 'mailpoet'),
  // translators: used as a search keyword for "WordPress user role changes" automation trigger
  __('role', 'mailpoet'),
  // translators: used as a search keyword for "WordPress user role changes" automation trigger
  __('change', 'mailpoet'),
];

export const step: StepType = {
  key: 'mailpoet:user-role-changed',
  group: 'triggers',
  title: () => __('WordPress user role changes', 'mailpoet'),
  description: () =>
    __('Start the automation when a WordPress user role changes.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => wordpress,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_user_role_changed',
      }}
    >
      {__(
        'Triggering an automation when a WordPress user role changes is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
