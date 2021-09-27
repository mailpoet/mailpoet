import React, { useState } from 'react';
import {
  __,
  assoc,
  compose,
  isEmpty,
} from 'lodash/fp';
import { useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import { Grid } from 'common/grid';
import Button from 'common/button/button';
import APIErrorsNotice from 'notices/api_errors_notice';

import { Scheduling } from './scheduling';
import ListingHeadingStepsRoute from '../../listings/heading_steps_route';

export function NewsletterTypeReEngagement(): JSX.Element {
  let defaultAfterTime = '';
  if (!isEmpty(MailPoet.settings.deactivate_subscriber_after_inactive_days)) {
    defaultAfterTime = (
      (
        Math.floor(
          Number(
            MailPoet.settings.deactivate_subscriber_after_inactive_days
          ) / 30
        )
      ) - 1
    ).toString();
  }

  const [options, setOptions] = useState({
    afterTimeNumber: defaultAfterTime,
    afterTimeType: 'months',
  });
  const [errors, setErrors] = useState([]);
  const [loading, setLoading] = useState(false);
  const history = useHistory();

  function showTemplateSelection(newsletterId: string) {
    history.push(`/template/${newsletterId}`);
  }

  function handleNext() {
    setErrors([]);
    setLoading(true);
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 're_engagement',
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
        options,
      },
    }).done((response) => {
      showTemplateSelection(response.data.id);
    }).fail((response) => {
      setLoading(false);
      if (response.errors) {
        setErrors(response.errors);
      }
    });
  }

  return (
    <div>
      <Background color="#fff" />

      {errors && (<APIErrorsNotice errors={errors} />)}

      <ListingHeadingStepsRoute emailType="re_engagement" automationId="re_engagement_heading_creation_heading" />

      <Grid.Column align="center" className="mailpoet-schedule-email">
        <Scheduling
          afterTimeNumber={options.afterTimeNumber}
          afterTimeType={options.afterTimeType}
          inactiveSubscribersPeriod={
            Number(MailPoet.settings.deactivate_subscriber_after_inactive_days)
          }
          updateAfterTimeNumber={compose([setOptions, assoc('afterTimeNumber', __, options)])}
          updateAfterTimeType={compose([setOptions, assoc('afterTimeType', __, options)])}
        />

        <Button
          isFullWidth
          onClick={handleNext}
          type="button"
          isDisabled={isEmpty(options.afterTimeNumber) || loading}
          withSpinner={loading}
        >
          {MailPoet.I18n.t('next')}
        </Button>
      </Grid.Column>
    </div>
  );
}
