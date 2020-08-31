import Hooks from 'hooks';
import React from 'react';
import { Location } from 'history';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import NoAccessInfo from './no_access_info';

type Props = {
  params: {
    id: string,
  },
  location: Location,
};

const OpenedEmailsStats = ({ params, location }:Props) => (
  <>
    <Heading level={2}>
      {MailPoet.I18n.t('openedEmailsHeading')}
    </Heading>
    {!MailPoet.premiumActive || MailPoet.subscribersLimitReached ? (
      <NoAccessInfo
        limitReached={MailPoet.subscribersLimitReached}
        limitValue={MailPoet.subscribersLimit}
        subscribersCount={MailPoet.subscribersCount}
        premiumActive={MailPoet.premiumActive}
        hasValidApiKey={MailPoet.hasValidApiKey}
      />
    ) : (
      Hooks.applyFilters('mailpoet_subscribers_opened_emails_stats', params, location)
    )}
  </>
);

export default OpenedEmailsStats;
