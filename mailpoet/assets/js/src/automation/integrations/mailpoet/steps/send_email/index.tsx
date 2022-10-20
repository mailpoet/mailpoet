import { __ } from '@wordpress/i18n';
import { Icon } from './icon';
import { Edit } from './edit';
import { StepType } from '../../../../editor/store/types';

export const step: StepType = {
  key: 'mailpoet:send-email',
  group: 'actions',
  title: __('Send email', 'mailpoet'),
  description: __('An email will be sent to subscriber', 'mailpoet'),
  subtitle: (data) =>
    (data.args.name as string) ?? __('Send email', 'mailpoet'),
  foreground: '#996800',
  background: '#FCF9E8',
  icon: Icon,
  edit: Edit,
} as const;
