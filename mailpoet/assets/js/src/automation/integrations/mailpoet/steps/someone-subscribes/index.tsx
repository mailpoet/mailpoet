import { __, _x } from '@wordpress/i18n';
import { commentAuthorAvatar } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';

const keywords = [
  // translators: verb, used as a search keyword for "Someone subscribes" trigger
  __('subscribe', 'mailpoet'),
  // translators: used as a search keyword for "Someone subscribes" trigger
  __('new subscriber', 'mailpoet'),
  // translators: noun, used as a search keyword for "Someone subscribes" trigger
  __('subscription', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:someone-subscribes',
  group: 'triggers',
  title: () => __('Someone subscribes', 'mailpoet'),
  description: () =>
    __(
      'Starts the automation when a new subscriber is added to MailPoet.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.4' }}>
      {commentAuthorAvatar}
    </div>
  ),
  edit: () => <Edit />,
} as const;
