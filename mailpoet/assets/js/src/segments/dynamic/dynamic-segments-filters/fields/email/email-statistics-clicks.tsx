import { useState, useEffect, useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { find, filter } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { APIErrorsNotice } from 'notices/api-errors-notice';
import { ReactSelect } from 'common/form/react-select/react-select';
import { Select } from 'common/form/select/select';
import {
  AnyValueTypes,
  EmailFormItem,
  FilterProps,
  SelectOption,
  WindowNewslettersList,
} from '../../../types';
import { storeName } from '../../../store';

const shouldDisplayLinks = (itemNewsletterId?: string): boolean =>
  !!itemNewsletterId;

export function EmailClickStatisticsFields({
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

  const [errors, setErrors] = useState([]);
  const [links, setLinks] = useState<SelectOption[]>([]);
  const [loadingLinks, setLoadingLinks] = useState<boolean>(false);

  const newsletterOptions = newslettersList?.map((newsletter) => {
    const sentAt = newsletter.sent_at
      ? MailPoet.Date.format(newsletter.sent_at)
      : __('Not sent yet', 'mailpoet');
    return {
      label: newsletter.name,
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
      <ReactSelect
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
        <option value={AnyValueTypes.NONE}>{MailPoet.I18n.t('noneOf')}</option>
      </Select>
      {loadingLinks && (
        <span>{MailPoet.I18n.t('loadingDynamicSegmentItems')}</span>
      )}
      {!loadingLinks && shouldDisplayLinks(segment.newsletter_id) && (
        <ReactSelect
          isMulti
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
    </>
  );
}
