import { useEffect } from 'react';
import { isValid, parseISO } from 'date-fns';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Datepicker } from 'common/datepicker/datepicker';
import { Grid } from 'common/grid';
import { Input } from 'common/form/input/input';

import { WordpressRoleFormItem } from '../types';

export enum SubscribedDateOperator {
  BEFORE = 'before',
  AFTER = 'after',
  ON = 'on',
  NOT_ON = 'notOn',
  IN_THE_LAST = 'inTheLast',
  NOT_IN_THE_LAST = 'notInTheLast',
}

const availableOperators = [
  SubscribedDateOperator.BEFORE,
  SubscribedDateOperator.AFTER,
  SubscribedDateOperator.ON,
  SubscribedDateOperator.NOT_ON,
  SubscribedDateOperator.IN_THE_LAST,
  SubscribedDateOperator.NOT_IN_THE_LAST,
];

const convertDateToString = (
  value: Date | [Date, Date],
): string | undefined => {
  if (value === null) {
    return undefined;
  }
  if (Array.isArray(value)) {
    throw new Error(
      'convertDateToString can process only single date array given',
    );
  }
  return MailPoet.Date.format(value, { format: 'Y-m-d' });
};

const parseDate = (value: string): Date | undefined => {
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

type Props = {
  filterIndex: number;
};

export function SubscribedDateFields({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  useEffect(() => {
    if (
      !availableOperators.includes(segment.operator as SubscribedDateOperator)
    ) {
      void updateSegmentFilter(
        { operator: SubscribedDateOperator.BEFORE },
        filterIndex,
      );
    }
    if (
      (segment.operator === SubscribedDateOperator.BEFORE ||
        segment.operator === SubscribedDateOperator.AFTER ||
        segment.operator === SubscribedDateOperator.ON ||
        segment.operator === SubscribedDateOperator.NOT_ON) &&
      (parseDate(segment.value) === undefined ||
        !/^\d+-\d+-\d+$/.test(segment.value))
    ) {
      void updateSegmentFilter(
        { value: convertDateToString(new Date()) },
        filterIndex,
      );
    }
    if (
      (segment.operator === SubscribedDateOperator.IN_THE_LAST ||
        segment.operator === SubscribedDateOperator.NOT_IN_THE_LAST) &&
      typeof segment.value === 'string' &&
      !/^\d*$/.exec(segment.value)
    ) {
      void updateSegmentFilter({ value: '' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select"
        value={segment.operator}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={SubscribedDateOperator.BEFORE}>
          {_x(
            'before',
            'Meaning: "Subscriber subscribed before April"',
            'mailpoet',
          )}
        </option>
        <option value={SubscribedDateOperator.AFTER}>
          {_x(
            'after',
            'Meaning: "Subscriber subscribed after April',
            'mailpoet',
          )}
        </option>
        <option value={SubscribedDateOperator.ON}>
          {_x(
            'on',
            'Meaning: "Subscriber subscribed on a given date"',
            'mailpoet',
          )}
        </option>
        <option value={SubscribedDateOperator.NOT_ON}>
          {_x(
            'not on',
            'Meaning: "Subscriber subscribed on a date other than the given date"',
            'mailpoet',
          )}
        </option>
        <option value={SubscribedDateOperator.IN_THE_LAST}>
          {_x(
            'in the last',
            'Appears together with `days` when creating a new WooCommerce segment based on the number of orders.',
            'mailpoet',
          )}
        </option>
        <option value={SubscribedDateOperator.NOT_IN_THE_LAST}>
          {_x(
            'not in the last',
            'Meaning: "Subscriber subscribed not in the last 3 days"',
            'mailpoet',
          )}
        </option>
      </Select>
      {(segment.operator === SubscribedDateOperator.BEFORE ||
        segment.operator === SubscribedDateOperator.AFTER ||
        segment.operator === SubscribedDateOperator.ON ||
        segment.operator === SubscribedDateOperator.NOT_ON) && (
        <Datepicker
          dateFormat="MMM d, yyyy"
          onChange={(value): void => {
            void updateSegmentFilter(
              { value: convertDateToString(value) },
              filterIndex,
            );
          }}
          selected={segment.value ? parseDate(segment.value) : undefined}
        />
      )}
      {(segment.operator === SubscribedDateOperator.IN_THE_LAST ||
        segment.operator === SubscribedDateOperator.NOT_IN_THE_LAST) && (
        <>
          <Input
            key="input"
            type="number"
            value={segment.value}
            onChange={(e) => {
              void updateSegmentFilterFromEvent('value', filterIndex, e);
            }}
            min="1"
            placeholder={__('days', 'mailpoet')}
          />
          <span>{__('days', 'mailpoet')}</span>
        </>
      )}
    </Grid.CenteredRow>
  );
}
