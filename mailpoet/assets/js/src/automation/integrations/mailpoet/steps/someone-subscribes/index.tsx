import { __ } from '@wordpress/i18n';
import { commentAuthorAvatar } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';

export const step: StepType = {
  key: 'mailpoet:someone-subscribes',
  group: 'triggers',
  title: __('Someone subscribes', 'mailpoet'),
  foreground: '#2271b1',
  background: '#f0f6fc',
  description: __(
    'Starts the automation when a new subscriber is added to MailPoet.',
    'mailpoet',
  ),
  subtitle: () => __('Trigger', 'mailpoet'),
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.4' }}>
      {commentAuthorAvatar}
    </div>
  ),
  edit: () => <Edit />,
} as const;
