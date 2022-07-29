import { Icon } from './icon';
import { Edit } from './edit';
import { StepType } from '../../../../editor/store/types';

export const step: StepType = {
  key: 'mailpoet:send-email',
  group: 'actions',
  title: 'Send email',
  description: 'An email will be sent to subscriber',
  subtitle: (data) => `Email ID ${data.args.email_id as string}`,
  foreground: '#996800',
  background: '#FCF9E8',
  icon: Icon(),
  edit: Edit,
} as const;
