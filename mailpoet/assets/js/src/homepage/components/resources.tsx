import { MailPoet } from 'mailpoet';
import { ContentSection } from './content-section';

export function Resources(): JSX.Element {
  return (
    <ContentSection
      className="mailpoet-homepage-resources"
      heading={MailPoet.I18n.t('learnMoreAboutEmailMarketing')}
    >
      Todo
    </ContentSection>
  );
}
