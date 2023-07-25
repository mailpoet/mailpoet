import { useEffect, useState } from 'react';
import { useRouteMatch, useLocation } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Loading } from 'common/loading';
import { useGlobalContextValue } from 'context';

import { Heading } from 'common/typography/heading/heading';
import { StatsHeading } from './stats/heading';
import { Summary } from './stats/summary';
import { WoocommerceRevenues } from './stats/woocommerce_revenues';
import { OpenedEmailsStats } from './stats/opened_email_stats';
import { EngagementSummary } from './stats/engagement_summary';

export type StatsType = {
  email: string;
  total_sent: number;
  open: number;
  machine_open: number;
  click: number;
  engagement_score: number;
  last_engagement?: string;
  last_click?: string;
  last_open?: string;
  last_sending?: string;
  last_page_view?: string;
  last_purchase?: string;
  woocommerce: {
    currency: string;
    value: number;
    count: number;
    formatted: string;
    formatted_average: string;
  };
};

export function SubscriberStats(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();
  const location = useLocation();
  const [stats, setStats] = useState<StatsType | null>(null);
  const [loading, setLoading] = useState(true);
  const contextValue = useGlobalContextValue(window);
  const showError = contextValue.notices.error;

  useEffect(() => {
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'subscriberStats',
      action: 'get',
      data: {
        subscriber_id: match.params.id,
      },
    })
      .done((response) => {
        setStats(response.data as StatsType);
        setLoading(false);
      })
      .fail((response) => {
        setLoading(false);
        if (response.errors.length > 0) {
          showError(
            <>
              {response.errors.map((error) => (
                <p key={error.message}>{error.message}</p>
              ))}
            </>,
            { scroll: true },
          );
        }
      });
  }, [match.params.id, showError]);

  if (loading) {
    return <Loading />;
  }

  return (
    <div className="mailpoet-subscriber-stats">
      <StatsHeading email={stats.email} />
      <Heading level={4}>{MailPoet.I18n.t('engagementPeriodHeading')}</Heading>
      <div className="mailpoet-subscriber-stats-summary-grid">
        <Summary
          click={stats.click}
          open={stats.open}
          machineOpen={stats.machine_open}
          totalSent={stats.total_sent}
          subscriber={{
            id: Number(match.params.id),
            engagement_score: stats.engagement_score,
          }}
        />
        <EngagementSummary
          lastClick={stats.last_click}
          lastEngagement={stats.last_engagement}
          lastOpen={stats.last_open}
          lastPageView={stats.last_page_view}
          lastPurchase={stats.last_purchase}
          lastSending={stats.last_sending}
          wooCommerceActive={!!stats.woocommerce}
        />
        {stats.woocommerce && (
          <WoocommerceRevenues
            averageRevenueValue={stats.woocommerce.formatted_average}
            count={stats.woocommerce.count}
            revenueValue={stats.woocommerce.formatted}
          />
        )}
      </div>
      <OpenedEmailsStats params={match.params} location={location} />
    </div>
  );
}

SubscriberStats.displayName = 'SubscriberStats';
