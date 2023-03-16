import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { Icon } from './icon';
import { Edit } from './edit';
import { State, StepType } from '../../../../editor/store/types';
import { Step } from '../../../../editor/components/automation/types';

const keywords = ['email', 'send email', 'send'];
export const step: StepType = {
  key: 'mailpoet:send-email',
  group: 'actions',
  title: () => __('Send email', 'mailpoet'),
  description: () => __('An email will be sent to subscriber.', 'mailpoet'),
  keywords,
  subtitle: (data) =>
    (data.args.name as string) ?? __('Send email', 'mailpoet'),
  foreground: '#996800',
  background: '#FCF9E8',
  icon: Icon,
  edit: Edit,
  createStep: (stepData: Step, state: State) =>
    Hooks.applyFilters(
      'mailpoet.automation.send_email.create_step',
      stepData,
      state.automationData.id,
    ),
} as const;
