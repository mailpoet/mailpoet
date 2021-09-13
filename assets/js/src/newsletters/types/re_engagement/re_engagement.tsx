import React, { useState } from 'react';
import { __, assoc, compose } from 'lodash/fp';
import { useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import { Grid } from 'common/grid';
import Button from 'common/button/button';
import APIErrorsNotice from 'notices/api_errors_notice';

import { Scheduling } from './scheduling';
import ListingHeadingStepsRoute from '../../listings/heading_steps_route';

interface ReengagementWindow extends Window {
  settings: {
    deactivate_subscriber_after_inactive_days: string;
  }
}

declare let window: ReengagementWindow;

export function NewsletterTypeReEngagement(): JSX.Element {
  const [options, setOptions] = useState({
    afterTimeNumber: (
      (Number(window.settings.deactivate_subscriber_after_inactive_days) / 30) - 1
    ).toString(),
    afterTimeType: 'months',
  });
  const [errors, setErrors] = useState([]);
  const history = useHistory();

  function showTemplateSelection(newsletterId: string) {
    history.push(`/template/${newsletterId}`);
  }

  function handleNext() {
    setErrors([]);
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
            Number(window.settings.deactivate_subscriber_after_inactive_days)
          }
          updateAfterTimeNumber={compose([setOptions, assoc('afterTimeNumber', __, options)])}
          updateAfterTimeType={compose([setOptions, assoc('afterTimeType', __, options)])}
        />

        <Button
          isFullWidth
          onClick={handleNext}
          type="button"
        >
          {MailPoet.I18n.t('next')}
        </Button>
      </Grid.Column>
    </div>
  );
}
