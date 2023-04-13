import { __ } from '@wordpress/i18n';
import { tag } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';
import { PremiumModalForStepEdit } from '../../../../../common/premium_modal';

const keywords = [
  __('tag', 'mailpoet'),
  __('remove tag', 'mailpoet'),
  __('remove tags', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:remove-tag',
  group: 'actions',
  title: () => __('Remove tag', 'mailpoet'),
  description: () =>
    __('Remove a tag or multiple tags from a subscriber.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.4' }}>{tag}</div>
  ),
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_remove_tag',
      }}
    >
      {__('Removing tags is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
