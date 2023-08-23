import { FunctionComponent, useMemo } from 'react';
import { Hooks } from 'hooks';
import { Location } from 'history';
import { MailPoet } from 'mailpoet';
import { Heading } from 'common/typography/heading/heading';
import { NoAccessInfo } from './no_access_info';

type Props = {
  params: {
    id: string;
  };
  location: Location;
};

export function OpenedEmailsStats({ params, location }: Props): JSX.Element {
  const Content = useMemo(
    () =>
      Hooks.applyFilters(
        'mailpoet_subscribers_opened_emails_stats',
        () => (
          <NoAccessInfo
            limitReached={MailPoet.subscribersLimitReached}
            limitValue={MailPoet.subscribersLimit}
            subscribersCountTowardsLimit={MailPoet.subscribersCount}
            premiumActive={MailPoet.premiumActive}
            hasValidApiKey={MailPoet.hasValidApiKey}
            hasPremiumSupport={MailPoet.hasPremiumSupport}
          />
        ),
        params,
        location,
      ) as FunctionComponent,
    [location, params],
  );

  return (
    <>
      <Heading level={4}>{MailPoet.I18n.t('openedEmailsHeading')}</Heading>
      <Content />
    </>
  );
}
