import Hooks from 'hooks';
import { Location } from 'history';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import NoAccessInfo from './no_access_info';

type Props = {
  params: {
    id: string;
  };
  location: Location;
};

export default function OpenedEmailsStats({
  params,
  location,
}: Props): JSX.Element {
  return (
    <>
      <Heading level={4}>{MailPoet.I18n.t('openedEmailsHeading')}</Heading>
      {!MailPoet.premiumActive || MailPoet.subscribersLimitReached ? (
        <NoAccessInfo
          limitReached={MailPoet.subscribersLimitReached}
          limitValue={MailPoet.subscribersLimit}
          subscribersCountTowardsLimit={MailPoet.subscribersCount}
          premiumActive={MailPoet.premiumActive}
          hasValidApiKey={MailPoet.hasValidApiKey}
          hasPremiumSupport={MailPoet.hasPremiumSupport}
        />
      ) : (
        Hooks.applyFilters(
          'mailpoet_subscribers_opened_emails_stats',
          params,
          location,
        )
      )}
    </>
  );
}
