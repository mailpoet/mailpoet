import { useState, useEffect, useCallback } from 'react';
import { MailPoet } from 'mailpoet';
import { find, filter } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { APIErrorsNotice } from 'notices/api_errors_notice';
import { ReactSelect } from 'common/form/react_select/react_select';
import { Grid } from 'common/grid';
import { Select } from 'common/form/select/select';
import {
  AnyValueTypes,
  EmailFormItem,
  SelectOption,
  WindowNewslettersList,
} from '../types';

const shouldDisplayLinks = (itemNewsletterId?: string): boolean =>
  !!itemNewsletterId;

type Props = {
  filterIndex: number;
};

export function EmailClickStatisticsFields({
  filterIndex,
}: Props): JSX.Element {
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

  const [errors, setErrors] = useState([]);
  const [links, setLinks] = useState<SelectOption[]>([]);
  const [loadingLinks, setLoadingLinks] = useState<boolean>(false);

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

  function loadLinks(newsletterId: string): void {
    setErrors([]);
    setLoadingLinks(true);
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'newsletter_links',
      action: 'get',
      data: { newsletterId },
    })
      .then((response) => {
        const { data } = response;
        const loadedLinks = data.map((link) => ({
          value: link.id,
          label: link.url,
        }));
        setLoadingLinks(false);
        setLinks(loadedLinks as SelectOption[]);
      })
      .fail((response: ErrorResponse) => {
        setErrors(response.errors as { message: string }[]);
      });
  }

  const loadLinksCB = useCallback(() => {
    if (!shouldDisplayLinks(segment.newsletter_id)) return;
    setLinks([]);
    loadLinks(segment.newsletter_id);
  }, [segment.newsletter_id]);

  useEffect(() => {
    loadLinksCB();
  }, [loadLinksCB, segment.newsletter_id]);

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [segment.operator, filterIndex, updateSegmentFilter]);

  return (
    <>
      {errors.length > 0 && <APIErrorsNotice errors={errors} />}
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          placeholder={MailPoet.I18n.t('selectNewsletterPlaceholder')}
          options={newsletterOptions}
          value={find(['value', segment.newsletter_id], newsletterOptions)}
          onChange={(option: SelectOption): void => {
            void updateSegmentFilter(
              { newsletter_id: option.value, link_ids: [] },
              filterIndex,
            );
          }}
          automationId="segment-email"
        />
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <Select
          isMinWidth
          key="select-operator"
          value={segment.operator}
          onChange={(e) =>
            updateSegmentFilterFromEvent('operator', filterIndex, e)
          }
          automationId="select-operator"
        >
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
        {loadingLinks && (
          <span>{MailPoet.I18n.t('loadingDynamicSegmentItems')}</span>
        )}
        {!loadingLinks && shouldDisplayLinks(segment.newsletter_id) && (
          <ReactSelect
            isMulti
            dimension="small"
            isFullWidth
            automationId="segment-link-select"
            placeholder={MailPoet.I18n.t('allLinksPlaceholder')}
            options={
              links.length
                ? links
                : [
                    {
                      value: 0,
                      label: MailPoet.I18n.t('noLinksHint'),
                      isDisabled: true,
                    },
                  ]
            }
            value={filter((option) => {
              if (!segment.link_ids) return false;
              return segment.link_ids.indexOf(option.value) !== -1;
            }, links)}
            onChange={(options: SelectOption[]): void => {
              void updateSegmentFilter(
                { link_ids: (options || []).map((x) => x.value) },
                filterIndex,
              );
            }}
          />
        )}
      </Grid.CenteredRow>
    </>
  );
}
