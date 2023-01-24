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
  closable: boolean;
  onHide?: () => void;
};

export function Upsell({ closable, onHide }: Props): JSX.Element {
  return (
    <ContentSection
      className="mailpoet-homepage-upsell"
      heading={MailPoet.I18n.t('accelerateYourGrowth')}
      headingAfter={
        closable && onHide ? (
          <Button
            icon={closeSmall}
            onClick={onHide}
            label={MailPoet.I18n.t('close')}
          />
        ) : null
      }
    >
      <div className="mailpoet-homepage-upsell__content">
        <ul>
          <li>
            <Icon icon={trendingUp} />
            <span>{MailPoet.I18n.t('detailedAnalytics')}</span>
          </li>
          <li>
            <Icon icon={people} />
            <span>{MailPoet.I18n.t('advancedSubscriberSegmentation')}</span>
          </li>
          <li>
            <Icon icon={megaphone} />
            <span>{MailPoet.I18n.t('emailMarketingAutomations')}</span>
          </li>
          <li>
            <Icon icon={lifesaver} />
            <span>{MailPoet.I18n.t('prioritySupport')}</span>
          </li>
        </ul>
        <Button
          variant="primary"
          href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
            MailPoet.subscribersCount,
            MailPoet.currentWpUserEmail,
            'business',
            {
              utm_source: 'plugin',
              utm_medium: 'homepage',
              utm_campaign: 'upsell',
            },
          )}
        >
          {MailPoet.I18n.t('upgradePlan')}
        </Button>
      </div>
    </ContentSection>
  );
}
