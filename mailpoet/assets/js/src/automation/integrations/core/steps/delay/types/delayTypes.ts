import { SelectControl } from '@wordpress/components';

export type DelayTypes = SelectControl.Option & {
  subtitle: (value: number) => string;
};
export const DelayTypeOptions: DelayTypes[] = [
  {
    label: 'Hours',
    subtitle: (value: number) =>
      `Wait for ${value} ${value === 1 ? 'hour' : 'hours'}`,
    value: 'HOURS',
  },
  {
    label: 'Days',
    subtitle: (value: number) =>
      `Wait for ${value} ${value === 1 ? 'day' : 'days'}`,
    value: 'DAYS',
  },
  {
    label: 'Weeks',
    subtitle: (value: number) =>
      `Wait for ${value} ${value === 1 ? 'week' : 'weeks'}`,
    value: 'WEEKS',
  },
];
