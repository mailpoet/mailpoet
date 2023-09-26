import { SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

export type DelayTypes = SelectControl.Option & {
  subtitle: (value: number) => string;
};
export const DelayTypeOptions: DelayTypes[] = [
  {
    label: __('Minutes', 'mailpoet'),
    subtitle: (value: number) =>
      sprintf(
        _n('Wait for %d minute', 'Wait for %d minutes', value, 'mailpoet'),
        value,
      ),
    value: 'MINUTES',
  },
  {
    label: __('Hours', 'mailpoet'),
    subtitle: (value: number) =>
      sprintf(
        _n('Wait for %d hour', 'Wait for %d hours', value, 'mailpoet'),
        value,
      ),
    value: 'HOURS',
  },
  {
    label: __('Days', 'mailpoet'),
    subtitle: (value: number) =>
      sprintf(
        _n('Wait for %d day', 'Wait for %d days', value, 'mailpoet'),
        value,
      ),
    value: 'DAYS',
  },
  {
    label: __('Weeks', 'mailpoet'),
    subtitle: (value: number) =>
      sprintf(
        _n('Wait for %d week', 'Wait for %d weeks', value, 'mailpoet'),
        value,
      ),
    value: 'WEEKS',
  },
];
