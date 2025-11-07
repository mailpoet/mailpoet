import { __, _x } from '@wordpress/i18n';
import { wordpress } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';

const keywords = [
  'WordPress',
  // translators: noun, used as a search keyword for "WordPress user registers" trigger
  __('user', 'mailpoet'),
  // translators: verb, used as a search keyword for "WordPress user registers" trigger
  __('register', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:wp-user-registered',
  group: 'triggers',
  // translators: automation trigger title
  title: () => __('New user registered', 'mailpoet'),
  foreground: '#2271b1',
  background: '#f0f6fc',
  description: () =>
    __(
      'Starts when a new user account is created.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  icon: () => wordpress,
  edit: () => <Edit />,
} as const;
