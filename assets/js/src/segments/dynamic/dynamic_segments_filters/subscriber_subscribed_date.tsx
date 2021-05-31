import React, { useEffect } from 'react';
import { isValid, parseISO } from 'date-fns';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';
import Datepicker from 'common/datepicker/datepicker';
import { Grid } from 'common/grid';
import Input from 'common/form/input/input';

import {
  WordpressRoleFormItem,
} from '../types';

export enum SubscribedDateOperator {
  BEFORE = 'before',
  AFTER = 'after',
  IN_THE_LAST = 'inTheLast',
  NOT_IN_THE_LAST = 'notInTheLast',
}

const availableOperators = [
  SubscribedDateOperator.BEFORE,
  SubscribedDateOperator.AFTER,
  SubscribedDateOperator.IN_THE_LAST,
  SubscribedDateOperator.NOT_IN_THE_LAST,
];

const convertDateToString = (value: Date): string | undefined => {
  if (value === null) {
    return undefined;
  }
  return (MailPoet.Date.format(value, { format: 'Y-m-d' }));
};

const parseDate = (value: string): Date | undefined => {
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

export const SubscribedDateFields: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment, updateSegmentFromEvent } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    if (!availableOperators.includes((segment.operator as SubscribedDateOperator))) {
      updateSegment({ operator: SubscribedDateOperator.BEFORE });
    }
    if (
      (
        segment.operator === SubscribedDateOperator.BEFORE
        || segment.operator === SubscribedDateOperator.AFTER
      )
      && ((parseDate(segment.value) === undefined) || !new RegExp(/^\d+-\d+-\d+$/).test(segment.value))
    ) {
      updateSegment({ value: convertDateToString(new Date()) });
    }
    if (
      (
        segment.operator === SubscribedDateOperator.IN_THE_LAST
        || segment.operator === SubscribedDateOperator.NOT_IN_THE_LAST
      )
      && ((typeof segment.value === 'string') && !new RegExp(/^\d*$/).exec(segment.value))
    ) {
      updateSegment({ value: '' });
    }
  }, [updateSegment, segment]);

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select"
          value={segment.operator}
          onChange={(e) => {
            updateSegmentFromEvent('operator', e);
          }}
        >
          <option value={SubscribedDateOperator.BEFORE}>{MailPoet.I18n.t('before')}</option>
          <option value={SubscribedDateOperator.AFTER}>{MailPoet.I18n.t('after')}</option>
          <option value={SubscribedDateOperator.IN_THE_LAST}>{MailPoet.I18n.t('inTheLast')}</option>
          <option value={SubscribedDateOperator.NOT_IN_THE_LAST}>{MailPoet.I18n.t('notInTheLast')}</option>
        </Select>
        {(
          segment.operator === SubscribedDateOperator.BEFORE
          || segment.operator === SubscribedDateOperator.AFTER
        ) && (
          <Datepicker
            dateFormat="MMMM d, yyyy"
            onChange={(value): void => {
              updateSegment({ value: convertDateToString(value) });
            }}
            maxDate={new Date()}
            selected={segment.value ? parseDate(segment.value) : undefined}
          />
        )}
        {(
          segment.operator === SubscribedDateOperator.IN_THE_LAST
          || segment.operator === SubscribedDateOperator.NOT_IN_THE_LAST
        ) && (
          <>
            <Input
              key="input"
              type="number"
              value={segment.value}
              onChange={(e) => {
                updateSegmentFromEvent('value', e);
              }}
              min="1"
              placeholder={MailPoet.I18n.t('daysPlaceholder')}
            />
            <span>
              {MailPoet.I18n.t('daysPlaceholder')}
            </span>
          </>
        )}
      </Grid.CenteredRow>
    </>
  );
};
