import React, { useState } from 'react';

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
  const [options] = useState({
    afterTimeNumber: window.settings.deactivate_subscriber_after_inactive_days,
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
