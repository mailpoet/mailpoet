import { MailPoet } from 'mailpoet';
import { ContentSection } from './content-section';

export function SubscribersStats(): JSX.Element {
  return (
    <ContentSection
      heading={MailPoet.I18n.t('subscribersHeading')}
      description={MailPoet.I18n.t('subscribersSectionDescription')}
    >
      <p>Todo Subscribers stats content</p>
    </ContentSection>
  );
}
