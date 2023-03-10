import { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { filter, map } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';

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
      : __('Not sent yet', 'mailpoet');
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
          <option value={AnyValueTypes.ANY}>{__('any of', 'mailpoet')}</option>
          <option value={AnyValueTypes.ALL}>{__('all of', 'mailpoet')}</option>
          {segment.action !== EmailActionTypes.MACHINE_OPENED ? (
            <option value={AnyValueTypes.NONE}>
              {__('none of', 'mailpoet')}
            </option>
          ) : null}
        </Select>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          isMulti
          placeholder={__('Search emails', 'mailpoet')}
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
