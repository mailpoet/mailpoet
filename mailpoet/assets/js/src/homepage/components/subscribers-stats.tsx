import { MouseEvent } from 'react';
import { Icon } from '@wordpress/components';
import { trendingUp } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import classnames from 'classnames';
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
  const missingStats =
    listsChange.length === 0 &&
    globalChange.subscribed === 0 &&
    globalChange.unsubscribed === 0;
  return (
    <ContentSection
      className="mailpoet-subscribers-stats"
      heading={MailPoet.I18n.t('subscribersHeading')}
      description={MailPoet.I18n.t('subscribersSectionDescription')}
      headingAfter={
        !missingStats && (
          <span
            className={classnames({
              'mailpoet-decrease': globalChange.changePercent < 0,
            })}
          >
            <Icon icon={trendingUp} />
            {globalChange.changePercent < 0 && '-'}
            {Math.abs(globalChange.changePercent) > 1000
              ? '∞%'
              : `${Math.abs(globalChange.changePercent)}%`}
          </span>
        )
      }
    >
      {missingStats ? (
        <div className="mailpoet-subscribers-stats-empty">
          <Icon icon={trendingUp} viewBox="-4 -4 32 32" />
          {!hasForms ? (
            <>
              <p>
                {MailPoet.I18n.t('changesWillAppear')}
                <br />
                {MailPoet.I18n.t('starBySettingUpForm')}
              </p>
              <a
                href="admin.php?page=mailpoet-form-editor-template-selection"
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
                href="admin.php?page=mailpoet-newsletters#/new"
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
          <div className="mailpoet-subscribers-stats-global-change">
            <div>
              {MailPoet.I18n.t('newSubscribers')}
              <br />
              <span>{globalChange.subscribed}</span>
            </div>
            <div>
              {MailPoet.I18n.t('unsubscribedSubscribers')}
              <br />
              <span>{globalChange.unsubscribed}</span>
            </div>
          </div>
          {listsChange.length && (
            <table className="mailpoet-subscribers-stats-list-change-table">
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
                  <tr key={list.id}>
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
          )}
        </>
      )}
    </ContentSection>
  );
}
