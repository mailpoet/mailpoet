import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';
import { Icon } from './icon';

const keywords = [
  // translators: verb, used as a search keyword for "Unsubscribe" automation action
  __('unsubscribe', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:unsubscribe',
  group: 'actions',
  title: () => __('Unsubscribe', 'mailpoet'),
  description: () =>
    __('Unsubscribe the subscriber from all marketing emails.', 'mailpoet'),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_unsubscribe',
      }}
    >
      {__('Unsubscribing is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
