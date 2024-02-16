import { dateI18n, getSettings } from '@wordpress/date';
import { __ } from '@wordpress/i18n';
import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'datetime',
  fieldType: 'datetime',
  formatValue: ({ args, condition }) => {
    if (args.value === undefined) {
      return undefined;
    }

    const settings = getSettings();

    // in-the-last/not-in-the-last
    if (
      ['in-the-last', 'not-in-the-last'].includes(condition) &&
      typeof args.value === 'object' &&
      'number' in args.value &&
      'unit' in args.value
    ) {
      return `${args.value.number as number} ${
        {
          days: __('days', 'mailpoet'),
          weeks: __('weeks', 'mailpoet'),
          months: __('months', 'mailpoet'),
        }[args.value.unit as string] ?? __('unknown unit', 'mailpoet')
      }`;
    }

    // on-the-days-of-the-week
    if (condition === 'on-the-days-of-the-week') {
      return (Array.isArray(args.value) ? args.value : [])
        .map(
          (day: number) =>
            (settings.l10n.weekdays[day] as string) ??
            __('unknown day', 'mailpoet'),
        )
        .join(', ');
    }

    const isDate = condition === 'on' || condition === 'not-on';

    return dateI18n(
      isDate ? settings.formats.date : settings.formats.datetime,
      args.value as string,
      settings.timezone.string,
    );
  },
  validateArgs: (args, condition) => {
    const value = args.value;

    if (['in-the-last', 'not-in-the-last'].includes(condition)) {
      return (
        typeof value === 'object' &&
        'number' in value &&
        'unit' in value &&
        typeof value.number === 'number' &&
        typeof value.unit === 'string' &&
        ['days', 'weeks', 'months'].includes(value.unit)
      );
    }

    if (['is-set', 'is-not-set'].includes(condition)) {
      return value === undefined;
    }

    if (condition === 'on-the-days-of-the-week') {
      return (
        Array.isArray(value) &&
        value.every(
          (day) =>
            typeof day === 'number' && [0, 1, 2, 3, 4, 5, 6].includes(day),
        )
      );
    }

    return (
      typeof value === 'string' && new Date(value).toString() !== 'Invalid Date'
    );
  },
  edit: undefined,
};
