import { __ } from '@wordpress/i18n';

import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Birthday" trigger
  __('birthday', 'mailpoet'),
  // translators: noun, used as a search keyword for "Birthday" trigger
  __('anniversary', 'mailpoet'),
  // translators: noun, used as a search keyword for "Birthday" trigger
  __('annual', 'mailpoet'),
  // translators: noun, used as a search keyword for "Birthday" trigger
  __('date', 'mailpoet'),
];

export const step: StepType = {
  key: 'mailpoet:annual-date',
  group: 'triggers',
  // translators: automation trigger title
  title: () => __('Birthday', 'mailpoet'),
  description: () =>
    __(
      "Triggers annually based on a subscriber's birthday or other date field.",
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
        utm_campaign: 'create_automation_editor_annual_date',
      }}
    >
      {__('The Birthday trigger is a premium feature.', 'mailpoet')}
    </PremiumModalForStepEdit>
  ),
} as const;
