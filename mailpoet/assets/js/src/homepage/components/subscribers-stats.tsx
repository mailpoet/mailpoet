import { MouseEvent } from 'react';
import { Icon } from '@wordpress/components';
import { trendingUp } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { MailPoet } from 'mailpoet';
import { storeName } from 'homepage/store/store';
import { ListingsEngagementScore } from 'subscribers/listings_engagement_score';
import { ContentSection } from './content-section';

const handleCtaClick = (event: MouseEvent, cta: string, link: string) => {
  event.preventDefault();
  MailPoet.trackEvent(
    'Home Page Statistics Click',
    {
      ctaLabel: cta,
    },
    { send_immediately: true },
    () => {
      window.location.href = link;
    },
  );
};

export function SubscribersStats(): JSX.Element {
  const { globalChange, listsChange, hasForms } = useSelect(
    (select) => ({
      globalChange: select(storeName).getGlobalSubscriberStatsChange(),
      listsChange: select(storeName).getListsSubscriberStatsChange(),
      hasForms: select(storeName).getHasForms(),
    }),
    [],
  );
  return (
    <ContentSection
      heading={MailPoet.I18n.t('subscribersHeading')}
      description={MailPoet.I18n.t('subscribersSectionDescription')}
    >
      {listsChange.length === 0 &&
      globalChange.subscribed === 0 &&
      globalChange.unsubscribed === 0 ? (
        <div>
          <Icon icon={trendingUp} />
          {!hasForms ? (
            <>
              <p>
                {MailPoet.I18n.t('changesWillAppear')}
                <br />
                {MailPoet.I18n.t('starBySettingUpForm')}
              </p>
              <a
                href="#"
                onClick={(event) =>
                  handleCtaClick(
                    event,
                    'new form',
                    'admin.php?page=mailpoet-form-editor-template-selection',
                  )
                }
              >
                {MailPoet.I18n.t('createForm')}
              </a>
            </>
          ) : (
            <>
              <p>{MailPoet.I18n.t('subscriberCountHasNotChangeLongTime')}</p>
              <a
                href="#"
                onClick={(event) =>
                  handleCtaClick(
                    event,
                    'campaigns',
                    'admin.php?page=mailpoet-newsletters#/new',
                  )
                }
              >
                {MailPoet.I18n.t('exploreCampaigns')}
              </a>
            </>
          )}
        </div>
      ) : (
        <>
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
          {listsChange.length ? (
            <table>
              <thead>
                <tr>
                  <th>{MailPoet.I18n.t('listName')}</th>
                  <th>{MailPoet.I18n.t('listScore')}</th>
                  <th>{MailPoet.I18n.t('subscribedSubscribers')}</th>
                  <th>{MailPoet.I18n.t('unsubscribedSubscribers')}</th>
                </tr>
              </thead>
              <tbody>
                {listsChange.map((list) => (
                  <tr>
                    <td>
                      <a
                        href={`admin.php?page=mailpoet-subscribers#/page[1]/sort_by[created_at]/sort_order[desc]/group[all]/filter[segment=${list.id}]`}
                      >
                        {list.name}
                      </a>
                    </td>
                    <td>
                      <div className="mailpoet-listing-stats">
                        <ListingsEngagementScore
                          id={list.id}
                          engagementScore={list.averageEngagementScore}
                        />
                      </div>
                    </td>
                    <td>{list.subscribed}</td>
                    <td>{list.unsubscribed}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : null}
        </>
      )}
    </ContentSection>
  );
}
