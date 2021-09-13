import React, { useState } from 'react';
import { __, assoc, compose } from 'lodash/fp';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import { Grid } from 'common/grid';
import Button from 'common/button/button';
import ListingHeadingStepsRoute from '../../listings/heading_steps_route';
import { Scheduling } from './scheduling';

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
  return (
    <div>
      <Background color="#fff" />

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
          onClick={() => 5}
          type="button"
        >
          {MailPoet.I18n.t('next')}
        </Button>
      </Grid.Column>
    </div>
  );
}
