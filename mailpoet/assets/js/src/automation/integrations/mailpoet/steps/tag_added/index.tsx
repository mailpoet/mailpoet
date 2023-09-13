import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium_modal/locked_badge';
import { PremiumModalForStepEdit } from '../../../../../common/premium_modal';
import { Icon } from './icon';

const keywords = [
  __('tag', 'mailpoet'),
  __('tags', 'mailpoet'),
  __('label', 'mailpoet'),
  __('labels', 'mailpoet'),
  __('add tag', 'mailpoet'),
  __('add tags', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:subscriber-tag-added',
  group: 'triggers',
  title: () => __('Tag added to subscriber', 'mailpoet'),
  description: () =>
    __('Start the automation when a tag is added to a subscriber.', 'mailpoet'),

  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
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
