import { __ } from '@wordpress/i18n';
import { list } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';
import { PremiumModalForStepEdit } from '../../../../../common/premium_modal';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';

export const step: StepType = {
  key: 'mailpoet:add-to-list',
  group: 'actions',
  title: __('Add to list', 'mailpoet'),
  description: __('Add a subscriber to a list.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.12' }}>{list}</div>
  ),
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_add_to_list',
      }}
    >
      {__('Adding subscribers to lists is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
