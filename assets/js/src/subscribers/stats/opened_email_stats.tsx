import Hooks from 'hooks';
import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';

type Props = {
  params: {
    id: string,
  },
};

const OpenedEmailsStats = ({ params }:Props) => (
  <>
    <Heading level={2}>
      {MailPoet.I18n.t('openedEmailsHeading')}
    </Heading>
    {!MailPoet.premiumActive || MailPoet.subscribersLimitReached ? (
      <p>Todo: Show no access to opened emails</p>
    ) : (
      Hooks.applyFilters('mailpoet_subscribers_opened_emails_stats', params)
    )}
  </>
);

export default OpenedEmailsStats;
