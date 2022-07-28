import { useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { filter, map } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { ReactSelect } from 'common/form/react_select/react_select';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import {
  AnyValueTypes,
  EmailActionTypes,
  EmailFormItem,
  SelectOption,
  WindowNewslettersList,
} from '../types';

type Props = {
  filterIndex: number;
};

export function EmailOpenStatisticsFields({ filterIndex }: Props): JSX.Element {
  const segment: EmailFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  const newslettersList: WindowNewslettersList = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getNewslettersList(),
    [],
  );

  const newsletterOptions = newslettersList?.map((newsletter) => {
    const sentAt = newsletter.sent_at
      ? MailPoet.Date.format(newsletter.sent_at)
      : MailPoet.I18n.t('notSentYet');
    return {
      label: newsletter.subject,
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
      <Grid.CenteredRow>
        <Select
          key="select"
          isFullWidth
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
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
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
              { newsletters: map('value', options) },
              filterIndex,
            );
          }}
        />
      </Grid.CenteredRow>
    </>
  );
}
