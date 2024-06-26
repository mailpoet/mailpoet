import { __ } from '@wordpress/i18n';
import { tag } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Add tag" automation action
  __('tag', 'mailpoet'),
  // translators: noun, used as a search keyword for "Add tag" automation action
  __('tags', 'mailpoet'),
  // translators: noun, used as a search keyword for "Add tag" automation action
  __('label', 'mailpoet'),
  // translators: noun, used as a search keyword for "Add tag" automation action
  __('labels', 'mailpoet'),
  // translators: used as a search keyword for "Add tag" automation action
  __('add tag', 'mailpoet'),
  // translators: used as a search keyword for "Add tag" automation action
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
  icon: () => tag,
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
