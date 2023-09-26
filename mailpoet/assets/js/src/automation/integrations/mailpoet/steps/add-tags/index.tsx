import { __ } from '@wordpress/i18n';
import { tag } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';
import { PremiumModalForStepEdit } from '../../../../../common/premium_modal';

const keywords = [
  __('tag', 'mailpoet'),
  __('tags', 'mailpoet'),
  __('label', 'mailpoet'),
  __('labels', 'mailpoet'),
  __('add tag', 'mailpoet'),
  __('add tags', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:add-tag',
  group: 'actions',
  title: () => __('Add tag', 'mailpoet'),
  description: () =>
    __('Add a tag or multiple tags to a subscriber.', 'mailpoet'),
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
        utm_campaign: 'create_automation_editor_add_tag',
      }}
    >
      {__('Adding tags is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
