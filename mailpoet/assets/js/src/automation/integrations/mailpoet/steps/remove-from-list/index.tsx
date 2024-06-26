import { __ } from '@wordpress/i18n';
import { list } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  // translators: noun, used as a search keyword for "Remove from list" automation action
  __('list', 'mailpoet'),
  // translators: used as a search keyword for "Remove from list" automation action
  __('remove list', 'mailpoet'),
  // translators: used as a search keyword for "Remove from list" automation action
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
  icon: () => list,
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
