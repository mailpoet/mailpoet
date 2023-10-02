import { __ } from '@wordpress/i18n';
import { list } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  __('list', 'mailpoet'),
  __('remove list', 'mailpoet'),
  __('remove from list', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:remove-from-list',
  group: 'actions',
  title: () => __('Remove from list', 'mailpoet'),
  description: () => __('Remove a subscriber from a list.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.12' }}>{list}</div>
  ),
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_remove_from_list',
      }}
    >
      {__('Removing subscribers from lists is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
