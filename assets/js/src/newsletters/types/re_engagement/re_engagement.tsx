import React from 'react';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import { Grid } from 'common/grid';
import Button from 'common/button/button';
import ListingHeadingStepsRoute from '../../listings/heading_steps_route';
import Heading from '../../../common/typography/heading/heading';

export function NewsletterTypeReEngagement(): JSX.Element {
  return (
    <div>
      <Background color="#fff" />

      <ListingHeadingStepsRoute emailType="re_engagement" automationId="re_engagement_heading_creation_heading" />

      <Grid.Column align="center" className="mailpoet-schedule-email">
        <Heading level={4}>{MailPoet.I18n.t('selectEventToSendReEngagementEmail')}</Heading>

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
