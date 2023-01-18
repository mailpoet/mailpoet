import { MailPoet } from 'mailpoet';
import { useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';
import { ContentSection } from './content-section';

export function SubscribersStats(): JSX.Element {
  const { globalChange } = useSelect(
    (select) => ({
      globalChange: select(storeName).getGlobalSubscriberStatsChange(),
    }),
    [],
  );
  return (
    <ContentSection
      heading={MailPoet.I18n.t('subscribersHeading')}
      description={MailPoet.I18n.t('subscribersSectionDescription')}
    >
      <div>
        <div>
          {MailPoet.I18n.t('newSubscribers')}
          <br />
          {globalChange.subscribed}
        </div>
        <div>
          {MailPoet.I18n.t('unsubscribedSubscribers')}
          <br />
          {globalChange.unsubscribed}
        </div>
      </div>
    </ContentSection>
  );
}
