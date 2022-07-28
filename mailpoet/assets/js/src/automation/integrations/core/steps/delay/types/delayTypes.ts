import { SelectControl } from '@wordpress/components';

export type DelayTypes = SelectControl.Option & {
  singular: string;
  plural: string;
};
export const DelayTypeOptions: DelayTypes[] = [
  {
    label: 'Hours',
    singular: 'hour',
    plural: 'hours',
    value: 'HOURS',
  },
  {
    label: 'Days',
    singular: 'day',
    plural: 'days',
    value: 'DAYS',
  },
  {
    label: 'Weeks',
    singular: 'week',
    plural: 'weeks',
    value: 'WEEKS',
  },
];
