import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';
import { PremiumModalForStepEdit } from '../../../../../common/premium_modal';

const keywords = [
  __('tag', 'mailpoet'),
  __('tags', 'mailpoet'),
  __('label', 'mailpoet'),
  __('labels', 'mailpoet'),
  __('remove tag', 'mailpoet'),
  __('remove tags', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:subscriber-tag-removed',
  group: 'triggers',
  title: () => __('Tag removed from subscriber', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when a tag is removed from a subscriber.',
      'mailpoet',
    ),

  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => null,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_tag_removed',
      }}
    >
      {__(
        'Starting an automation when a tag has been removed from a subscriber is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
