import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';

const OpenedEmailsStats = () => (
  <>
    <Heading level={2}>
      {MailPoet.I18n.t('openedEmailsHeading')}
    </Heading>
    {!MailPoet.premiumActive || MailPoet.subscribersLimitReached ? (
      <p>Todo: Show no access to opened emails</p>
    ) : (
      <p>Todo: Call hook to display premium content</p>
    )}
  </>
);

export default OpenedEmailsStats;
