import { useEffect, useState } from 'react';
import { useRouteMatch, useLocation } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Loading } from 'common/loading';
import { useGlobalContextValue } from 'context';

import { StatsHeading as Heading } from './stats/heading';
import { Summary } from './stats/summary';
import { WoocommerceRevenues } from './stats/woocommerce_revenues';
import { OpenedEmailsStats } from './stats/opened_email_stats';

export type StatsType = {
  email: string;
  total_sent: number;
  open: number;
  machine_open: number;
  click: number;
  engagement_score: number;
  last_engagement?: string;
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
      <Heading email={stats.email} />
      <p>
        {MailPoet.I18n.t('lastEngagement')}
        {': '}
        {stats.last_engagement
          ? MailPoet.Date.format(stats.last_engagement)
          : MailPoet.I18n.t('never')}
      </p>
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
