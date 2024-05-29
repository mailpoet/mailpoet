import { useEffect, useState } from 'react';
import { useLocation, useRouteMatch } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Loading } from 'common/loading';

import { StatsHeading } from './stats/heading';
import { Summary } from './stats/summary';
import { WoocommerceRevenues } from './stats/woocommerce-revenues';
import { OpenedEmailsStats } from './stats/opened-email-stats';
import { EngagementSummary } from './stats/engagement-summary';
import { StatsType } from './types';

export function SubscriberStats(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();
  const location = useLocation();
  const [stats, setStats] = useState<StatsType | null>(null);
  const [loading, setLoading] = useState(true);

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
          MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
        }
      });
  }, [match.params.id]);

  if (loading) {
    return <Loading />;
  }

  if (!stats) return null;

  return (
    <div className="mailpoet-subscriber-stats">
      <StatsHeading email={stats.email} />
      <div className="mailpoet-subscriber-stats-summary-grid">
        <Summary
          stats={stats}
          subscriber={{
            id: Number(match.params.id),
            engagement_score: stats.engagement_score,
          }}
        />
        <EngagementSummary stats={stats} />
        {stats.is_woo_active && <WoocommerceRevenues stats={stats} />}
      </div>
      <OpenedEmailsStats params={match.params} location={location} />
    </div>
  );
}

SubscriberStats.displayName = 'SubscriberStats';
