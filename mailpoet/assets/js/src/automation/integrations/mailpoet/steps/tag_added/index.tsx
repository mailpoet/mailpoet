import { __ } from '@wordpress/i18n';
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
  key: 'mailpoet:tag-added',
  group: 'triggers',
  title: () => __('Tag added', 'mailpoet'),
  description: () =>
    __('Triggers when a subscriber has been added to a tag.', 'mailpoet'),

  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => null,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_tag_added',
      }}
    >
      {__(
        'Starting an automation when a tag was added to a subscriber is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
