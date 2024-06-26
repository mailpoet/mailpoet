import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Tag removed from subscriber" automation action
  __('tag', 'mailpoet'),
  // translators: noun, used as a search keyword for "Tag removed from subscriber" automation action
  __('tags', 'mailpoet'),
  // translators: noun, used as a search keyword for "Tag removed from subscriber" automation action
  __('label', 'mailpoet'),
  // translators: noun, used as a search keyword for "Tag removed from subscriber" automation action
  __('labels', 'mailpoet'),
  // translators: used as a search keyword for "Tag removed from subscriber" automation action
  __('remove tag', 'mailpoet'),
  // translators: used as a search keyword for "Tag removed from subscriber" automation action
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
  icon: () => <Icon />,
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
