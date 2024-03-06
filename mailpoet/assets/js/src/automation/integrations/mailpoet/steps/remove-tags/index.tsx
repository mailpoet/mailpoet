import { __ } from '@wordpress/i18n';
import { tag } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Remove tag" automation action
  __('tag', 'mailpoet'),
  // translators: noun, used as a search keyword for "Remove tag" automation action
  __('tags', 'mailpoet'),
  // translators: noun, used as a search keyword for "Remove tag" automation action
  __('label', 'mailpoet'),
  // translators: noun, used as a search keyword for "Remove tag" automation action
  __('labels', 'mailpoet'),
  // translators: used as a search keyword for "Remove tag" automation action
  __('remove tag', 'mailpoet'),
  // translators: used as a search keyword for "Remove tag" automation action
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
