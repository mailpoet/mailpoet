import { useEffect, useState } from 'react';
import { useLocation, useParams } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Loading } from 'common/loading';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';

import { StatsHeading } from './stats/heading';
import { Summary } from './stats/summary';
import { WoocommerceOverview } from './stats/woocommerce-overview';
import { OpenedEmailsStats } from './stats/opened-email-stats';
import { EngagementSummary } from './stats/engagement-summary';
import { StatsType } from './types';

export function SubscriberStats(): JSX.Element {
  const params = useParams();
  const location = useLocation();
  const [stats, setStats] = useState<StatsType | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'subscriberStats',
      action: 'get',
      data: {
        subscriber_id: params.id,
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
  }, [params.id]);

  if (loading) {
    return <Loading />;
  }

  if (!stats) return null;

  return (
    <>
      <TopBarWithBoundary hideScreenOptions />
      <div className="mailpoet-subscriber-stats">
        <StatsHeading
          email={stats.email}
          avatarUrl={stats.avatar_url}
          subscribedAt={stats.subscribed_at}
          sourceLabel={stats.source_label}
        />
        {stats.is_woo_active && stats.is_woocommerce_user && (
          <WoocommerceOverview stats={stats} />
        )}
        <div className="mailpoet-subscriber-stats-summary-grid">
          <Summary
            stats={stats}
            subscriber={{
              id: Number(params.id),
              engagement_score: stats.engagement_score,
            }}
          />
          <EngagementSummary stats={stats} />
        </div>
        <OpenedEmailsStats params={params} location={location} />
      </div>
    </>
  );
}

SubscriberStats.displayName = 'SubscriberStats';
