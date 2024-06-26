import { __, _x } from '@wordpress/i18n';
import { wordpress } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';

const keywords = [
  // translators: noun, used as a search keyword for "WordPress user registers" trigger
  __('wordpress', 'mailpoet'),
  // translators: noun, used as a search keyword for "WordPress user registers" trigger
  __('user', 'mailpoet'),
  // translators: verb, used as a search keyword for "WordPress user registers" trigger
  __('register', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:wp-user-registered',
  group: 'triggers',
  title: () => __('WordPress user registers', 'mailpoet'),
  foreground: '#2271b1',
  background: '#f0f6fc',
  description: () =>
    __(
      'Starts the automation when a new user registered in WordPress.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  icon: () => wordpress,
  edit: () => <Edit />,
} as const;
