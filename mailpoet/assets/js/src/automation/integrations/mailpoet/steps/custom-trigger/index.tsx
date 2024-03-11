import { __ } from '@wordpress/i18n';
import { plugins } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Custom trigger" in automation
  __('custom', 'mailpoet'),
  // translators: noun, used as a search keyword for "Custom trigger" in automation
  __('hook', 'mailpoet'),
  // translators: noun, used as a search keyword for "Custom trigger" in automation
  __('code', 'mailpoet'),
];

export const step: StepType = {
  key: 'mailpoet:custom-trigger',
  group: 'triggers',
  title: () => __('Custom trigger', 'mailpoet'),
  description: () =>
    __(
      "This is an advanced feature for developers. Triggers an automation when a certain action is fired. The action's first argument must be the email address of a subscriber.",
      'mailpoet',
    ),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.4' }}>{plugins}</div>
  ),
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_custom_trigger',
      }}
    >
      {__(
        'Triggering an automation with a custom hook is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
