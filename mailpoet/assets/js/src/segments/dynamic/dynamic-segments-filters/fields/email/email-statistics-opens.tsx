import { useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import { ReactSelect } from 'common/form/react-select/react-select';
import { Select } from 'common/form/select/select';
import {
  AnyValueTypes,
  EmailActionTypes,
  EmailFormItem,
  FilterProps,
  WindowNewslettersList,
} from '../../../types';
import { storeName } from '../../../store';
import {
  getGroupedNewsletterOptions,
  NewsletterOption,
} from './newsletter-options';

export function EmailOpenStatisticsFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: EmailFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  const newslettersList: WindowNewslettersList = useSelect(
    (select) => select(storeName).getNewslettersList(),
    [],
  );

  const { flatOptions, groupedOptions } =
    getGroupedNewsletterOptions(newslettersList);

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
    // None is not allowed for Machine Opened
    if (
      segment.action === EmailActionTypes.MACHINE_OPENED &&
      segment.operator === AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [segment.action, segment.operator, filterIndex, updateSegmentFilter]);

  return (
    <>
      <Select
        key="select"
        isMinWidth
        automationId="segment-email-opens-condition"
        value={segment.operator}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
        <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
        {segment.action !== EmailActionTypes.MACHINE_OPENED ? (
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        ) : null}
      </Select>
      <ReactSelect
        dimension="small"
        isMulti
        placeholder={MailPoet.I18n.t('selectNewsletterPlaceholder')}
        options={groupedOptions}
        automationId="segment-email"
        value={flatOptions.filter(({ value }) =>
          segment.newsletters?.includes(value),
        )}
        onChange={(options: NewsletterOption[]): void => {
          void updateSegmentFilter(
            { newsletters: (options || []).map(({ value }) => value) },
            filterIndex,
          );
        }}
      />
    </>
  );
}
