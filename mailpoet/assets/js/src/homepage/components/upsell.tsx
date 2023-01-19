import { MailPoet } from 'mailpoet';
import {
  closeSmall,
  lifesaver,
  megaphone,
  people,
  trendingUp,
} from '@wordpress/icons';
import { Button, Icon } from '@wordpress/components';
import { ContentSection } from './content-section';

type Props = {
  onHide: () => void;
};

export function Upsell({ onHide }: Props): JSX.Element {
  return (
    <ContentSection
      className="mailpoet-homepage-upsell"
      heading={MailPoet.I18n.t('accelerateYourGrowth')}
      headingAfter={
        <Button
          icon={closeSmall}
          onClick={onHide}
          label={MailPoet.I18n.t('close')}
        />
      }
    >
      <ul>
        <li>
          <Icon icon={trendingUp} />
          {MailPoet.I18n.t('detailedAnalytics')}
        </li>
        <li>
          <Icon icon={people} />
          {MailPoet.I18n.t('advancedSubscriberSegmentation')}
        </li>
        <li>
          <Icon icon={megaphone} />
          {MailPoet.I18n.t('emailMarketingAutomations')}
        </li>
        <li>
          <Icon icon={lifesaver} />
          {MailPoet.I18n.t('prioritySupport')}
        </li>
      </ul>
      <Button variant="primary">{MailPoet.I18n.t('upgradePlan')}</Button>
    </ContentSection>
  );
}
