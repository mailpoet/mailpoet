import React, { useState, useEffect, useCallback } from 'react';
import MailPoet from 'mailpoet';
import { find } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import APIErrorsNotice from 'notices/api_errors_notice';
import ReactSelect from 'common/form/react_select/react_select';
import { Grid } from 'common/grid';
import {
  EmailFormItem,
  SelectOption,
  WindowNewslettersList,
} from '../types';

const shouldDisplayLinks = (itemNewsletterId?: string): boolean => (!!itemNewsletterId);

type Props = {
  filterIndex: number;
}

export const EmailClickStatisticsFields: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const segment: EmailFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );

  const { updateSegmentFilter } = useDispatch('mailpoet-dynamic-segments-form');

  const newslettersList: WindowNewslettersList = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getNewslettersList(),
    []
  );

  const [errors, setErrors] = useState([]);
  const [links, setLinks] = useState<SelectOption[]>([]);
  const [loadingLinks, setLoadingLinks] = useState<boolean>(false);

  const newsletterOptions = newslettersList?.map((newsletter) => {
    const sentAt = (newsletter.sent_at) ? MailPoet.Date.format(newsletter.sent_at) : MailPoet.I18n.t('notSentYet');
    return {
      label: newsletter.subject,
      tag: sentAt,
      value: Number(newsletter.id),
    };
  });

  function loadLinks(newsletterId: string): void {
    setErrors([]);
    setLoadingLinks(true);
    MailPoet.Ajax.post({
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
        setLinks(loadedLinks);
      })
      .fail((response) => {
        setErrors(response.errors);
      });
  }

  const loadLinksCB = useCallback(() => {
    if (!shouldDisplayLinks(segment.newsletter_id)) return;
    setLinks([]);
    loadLinks(segment.newsletter_id);
  }, [segment.newsletter_id]);

  useEffect(() => {
    loadLinksCB();
  }, [
    loadLinksCB,
    segment.newsletter_id,
  ]);

  return (
    <>
      {(errors.length > 0 && (
        <APIErrorsNotice errors={errors} />
      ))}
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          placeholder={MailPoet.I18n.t('selectNewsletterPlaceholder')}
          options={newsletterOptions}
          value={find(['value', segment.newsletter_id], newsletterOptions)}
          onChange={(option: SelectOption): void => {
            updateSegmentFilter({ newsletter_id: option.value }, filterIndex);
          }}
          automationId="segment-email"
        />
      </Grid.CenteredRow>
      {(loadingLinks && (MailPoet.I18n.t('loadingDynamicSegmentItems')))}
      {
        (!!links.length && shouldDisplayLinks(segment.newsletter_id))
        && (
          <div>
            <ReactSelect
              dimension="small"
              isFullWidth
              placeholder={MailPoet.I18n.t('selectLinkPlaceholder')}
              options={links}
              value={find(['value', Number(segment.link_id)], links)}
              onChange={(option: SelectOption): void => {
                updateSegmentFilter({ link_id: option.value }, filterIndex);
              }}
            />
          </div>
        )
      }
    </>
  );
};
