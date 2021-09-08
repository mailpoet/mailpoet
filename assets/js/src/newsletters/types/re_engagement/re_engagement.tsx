import React from 'react';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import { Grid } from 'common/grid';
import Button from 'common/button/button';

export function NewsletterTypeReEngagement(): JSX.Element {
  return (
    <div>
      <Background color="#fff" />

      <Grid.Column align="center" className="mailpoet-schedule-email">
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
