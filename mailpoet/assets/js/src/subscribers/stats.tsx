import { useEffect, useState } from 'react';
import { useLocation, useRouteMatch } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Loading } from 'common/loading';
import { useGlobalContextValue } from 'context';

import { Heading } from 'common';
import { StatsHeading } from './stats/heading';
import { Summary } from './stats/summary';
import { WoocommerceRevenues } from './stats/woocommerce_revenues';
import { OpenedEmailsStats } from './stats/opened_email_stats';
import { EngagementSummary } from './stats/engagement_summary';
import { StatsType } from './types';

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
        <EngagementSummary stats={stats} />
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
