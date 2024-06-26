import { __ } from '@wordpress/i18n';
import { list } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  // translators: noun, used as a search keyword for "Add to list" automation action
  __('list', 'mailpoet'),
  // translators: used as a search keyword for "Add to list" automation action
  __('add list', 'mailpoet'),
  // translators: used as a search keyword for "Add to list" automation action
  __('add to list', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:add-to-list',
  group: 'actions',
  title: () => __('Add to list', 'mailpoet'),
  description: () => __('Add a subscriber to a list.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => list,
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
