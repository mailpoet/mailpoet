import { useEffect, useState } from 'react';
import { useLocation, useParams } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Loading } from 'common/loading';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';

import { StatsHeading } from './stats/heading';
import { WoocommerceOverview } from './stats/woocommerce-overview';
import { EngagementCard } from './stats/engagement-card';
import { ProfileInformation } from './stats/profile-information';
import { ActivityShell } from './stats/activity-shell';
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
        <div className="mailpoet-subscriber-stats-content">
          <div className="mailpoet-subscriber-stats-primary-column">
            <EngagementCard stats={stats} />
            <ProfileInformation
              profile={stats.profile}
              subscriberId={Number(params.id)}
            />
          </div>
          <ActivityShell
            lastEngagementAt={stats.last_engagement_at || stats.last_engagement}
            params={params}
            location={location}
          />
        </div>
      </div>
    </>
  );
}

SubscriberStats.displayName = 'SubscriberStats';
