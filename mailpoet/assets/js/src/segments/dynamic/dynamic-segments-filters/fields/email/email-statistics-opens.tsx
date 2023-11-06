import { useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { filter, map, parseInt } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { ReactSelect } from 'common/form/react-select/react-select';
import { Select } from 'common/form/select/select';
import {
  AnyValueTypes,
  EmailActionTypes,
  EmailFormItem,
  FilterProps,
  SelectOption,
  WindowNewslettersList,
} from '../../../types';
import { storeName } from '../../../store';

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

  const newsletterOptions = newslettersList?.map((newsletter) => {
    const sentAt = newsletter.sent_at
      ? MailPoet.Date.format(newsletter.sent_at)
      : MailPoet.I18n.t('notSentYet');
    return {
      label: newsletter.name,
      tag: sentAt,
      value: Number(newsletter.id),
    };
  });

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
        options={newsletterOptions}
        automationId="segment-email"
        value={filter((option) => {
          if (!segment.newsletters) return undefined;
          const newsletterId = option.value;
          return segment.newsletters.indexOf(newsletterId) !== -1;
        }, newsletterOptions)}
        onChange={(options: SelectOption[]): void => {
          void updateSegmentFilter(
            { newsletters: map(parseInt(10), map('value', options)) },
            filterIndex,
          );
        }}
      />
    </>
  );
}
