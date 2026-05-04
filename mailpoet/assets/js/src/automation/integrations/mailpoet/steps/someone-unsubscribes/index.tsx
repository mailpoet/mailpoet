import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';

const keywords = [
  // translators: verb, used as a search keyword for "Someone unsubscribes" trigger
  __('unsubscribe', 'mailpoet'),
  // translators: used as a search keyword for "Someone unsubscribes" trigger
  __('opt out', 'mailpoet'),
];

export const stepKey = 'mailpoet:someone-unsubscribes';

export const step: StepType = {
  key: stepKey,
  group: 'triggers',
  // translators: automation trigger title
  title: () => __('Email subscriber unsubscribed', 'mailpoet'),
  description: () =>
    __(
      'Starts when a subscriber unsubscribes from your email list.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => null,
} as const;
