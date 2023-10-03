import { __ } from '@wordpress/i18n';
import { code } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';
import { PremiumModalForStepEdit } from '../../../../../common/premium_modal';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';

const keywords = [
  __('custom', 'mailpoet'),
  __('hook', 'mailpoet'),
  __('code', 'mailpoet'),
];

export const step: StepType = {
  key: 'mailpoet:custom-action',
  group: 'actions',
  title: () => __('Custom action', 'mailpoet'),
  description: () => __('Fires a customizable hook.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.12' }}>{code}</div>
  ),
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_custom_action',
      }}
    >
      {__('Firing a custom hook is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
