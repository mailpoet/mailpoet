import { __ } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { Icon } from './icon';
import { Edit } from './edit';
import { StepType } from '../../../../editor/store/types';

const keywords = [
  // translators: noun, used as a search keyword for "Scheduled date/time" trigger
  __('schedule', 'mailpoet'),
  // translators: noun, used as a search keyword for "Scheduled date/time" trigger
  __('date', 'mailpoet'),
  // translators: noun, used as a search keyword for "Scheduled date/time" trigger
  __('time', 'mailpoet'),
  // translators: noun, used as a search keyword for "Scheduled date/time" trigger
  __('calendar', 'mailpoet'),
];

export const step: StepType = {
  key: 'core:scheduled-date-time',
  group: 'triggers',
  // translators: automation trigger title
  title: () => __('Run at a specific date and time', 'mailpoet'),
  description: () =>
    __(
      'Starts the automation at a scheduled date and time for subscribers in selected lists.',
      'mailpoet',
    ),
  subtitle: (data) => {
    if (!data?.args?.scheduled_at) {
      return __('Not set up yet', 'mailpoet');
    }
    const settings = getSettings();
    return dateI18n(
      settings.formats.datetime,
      data.args.scheduled_at as string,
      settings.timezone.string,
    );
  },
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: Icon,
  edit: () => <Edit />,
} as const;
