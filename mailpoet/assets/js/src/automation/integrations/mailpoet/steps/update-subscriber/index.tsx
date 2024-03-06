import { __ } from '@wordpress/i18n';
import { postAuthor } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';

const keywords = [
  // translators: verb, used as a search keyword for "Update subscriber" automation action
  __('update', 'mailpoet'),
  // translators: used as a search keyword for "Update subscriber" automation action
  __('update subscriber', 'mailpoet'),
  // translators: used as a search keyword for "Update subscriber" automation action
  __('update custom field', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:update-subscriber',
  group: 'actions',
  title: () => __('Update subscriber', 'mailpoet'),
  description: () =>
    __('Update the subscriber’s custom field to a specific value.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.3' }}>
      {postAuthor}
    </div>
  ),
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_update_subscriber',
      }}
    >
      {__('Updating subscribers is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
