import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';

import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import { Input } from 'common/form/input/input';

import { WordpressRoleFormItem } from '../types';

export enum SubscriberScoreOperator {
  HIGHER_THAN = 'higherThan',
  LOWER_THAN = 'lowerThan',
  EQUALS = 'equals',
  NOT_EQUALS = 'not_equals',
  UNKNOWN = 'unknown',
  NOT_UNKNOWN = 'not_unknown',
}

const availableOperators = [
  SubscriberScoreOperator.HIGHER_THAN,
  SubscriberScoreOperator.LOWER_THAN,
  SubscriberScoreOperator.EQUALS,
  SubscriberScoreOperator.NOT_EQUALS,
  SubscriberScoreOperator.UNKNOWN,
  SubscriberScoreOperator.NOT_UNKNOWN,
];

type Props = {
  filterIndex: number;
};

export function validateSubscriberScore(
  formItems: WordpressRoleFormItem,
): boolean {
  if (
    formItems.operator === SubscriberScoreOperator.HIGHER_THAN ||
    formItems.operator === SubscriberScoreOperator.LOWER_THAN ||
    formItems.operator === SubscriberScoreOperator.EQUALS ||
    formItems.operator === SubscriberScoreOperator.NOT_EQUALS
  ) {
    const numericValue = Number(formItems.value);
    const numericFloatValue = Number(parseFloat(formItems.value));
    return (
      !Number.isNaN(numericValue) &&
      !Number.isNaN(numericFloatValue) &&
      numericValue >= 0 &&
      numericValue <= 100
    );
  }
  if (
    formItems.operator === SubscriberScoreOperator.UNKNOWN ||
    formItems.operator === SubscriberScoreOperator.NOT_UNKNOWN
  ) {
    return true;
  }
  return false;
}

function replaceSubscriberScoreSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return _x(
    '{condition} {score} %',
    'The result will be "higher than 20 %"',
    'mailpoet',
  )
    .split(/({condition})|({score})|(\b%\b)/gim)
    .map(fn);
}

export function SubscriberScoreFields({ filterIndex }: Props): JSX.Element {
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
      !availableOperators.includes(segment.operator as SubscriberScoreOperator)
    ) {
      void updateSegmentFilter(
        { operator: SubscriberScoreOperator.HIGHER_THAN },
        filterIndex,
      );
    }
    if (
      (segment.operator === SubscriberScoreOperator.HIGHER_THAN ||
        segment.operator === SubscriberScoreOperator.LOWER_THAN ||
        segment.operator === SubscriberScoreOperator.EQUALS ||
        segment.operator === SubscriberScoreOperator.NOT_EQUALS) &&
      typeof segment.value === 'undefined'
    ) {
      void updateSegmentFilter({ value: '' }, filterIndex);
    }
    if (
      segment.operator === SubscriberScoreOperator.UNKNOWN ||
      segment.operator === SubscriberScoreOperator.NOT_UNKNOWN
    ) {
      void updateSegmentFilter({ value: null }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <Grid.CenteredRow>
      {replaceSubscriberScoreSentence((match) => {
        if (match === '{condition}') {
          return (
            <Select
              key="select"
              value={segment.operator}
              automationId="segment-subscriber-score-operator"
              onChange={(e) => {
                void updateSegmentFilterFromEvent('operator', filterIndex, e);
              }}
            >
              <option value={SubscriberScoreOperator.HIGHER_THAN}>
                {__('higher than', 'mailpoet')}
              </option>
              <option value={SubscriberScoreOperator.LOWER_THAN}>
                {__('lower than', 'mailpoet')}
              </option>
              <option value={SubscriberScoreOperator.EQUALS}>
                {__('equals', 'mailpoet')}
              </option>
              <option value={SubscriberScoreOperator.NOT_EQUALS}>
                {__('not equals', 'mailpoet')}
              </option>
              <option value={SubscriberScoreOperator.UNKNOWN}>
                {__('unknown', 'mailpoet')}
              </option>
              <option value={SubscriberScoreOperator.NOT_UNKNOWN}>
                {__('not unknown', 'mailpoet')}
              </option>
            </Select>
          );
        }
        if (match === '{score}') {
          return (
            (segment.operator === SubscriberScoreOperator.HIGHER_THAN ||
              segment.operator === SubscriberScoreOperator.LOWER_THAN ||
              segment.operator === SubscriberScoreOperator.EQUALS ||
              segment.operator === SubscriberScoreOperator.NOT_EQUALS) && (
              <Input
                key="input"
                type="number"
                value={segment.value || ''}
                data-automation-id="segment-subscriber-score-value"
                onChange={(e) => {
                  void updateSegmentFilterFromEvent('value', filterIndex, e);
                }}
                min="0"
                placeholder={_x(
                  'score',
                  'Placeholder for input: subscriber engagement score',
                  'mailpoet',
                )}
              />
            )
          );
        }
        if (typeof match === 'string' && match.trim().length > 0) {
          return (
            segment.operator !== SubscriberScoreOperator.UNKNOWN &&
            segment.operator !== SubscriberScoreOperator.NOT_UNKNOWN && (
              <div key="score">{match}</div>
            )
          );
        }
        return null;
      })}
    </Grid.CenteredRow>
  );
}
