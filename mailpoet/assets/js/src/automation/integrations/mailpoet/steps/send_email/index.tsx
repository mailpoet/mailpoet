import { Icon } from './icon';
import { Edit } from './edit';

export const step = {
  key: 'mailpoet:send-email',
  title: 'Send email',
  description: 'An email will be sent to subscriber',
  icon: Icon,
  edit: Edit,
} as const;
