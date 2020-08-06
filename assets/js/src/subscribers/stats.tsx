import React, { useEffect, useState } from 'react';
import {
  useRouteMatch,
} from 'react-router-dom';
import MailPoet from 'mailpoet';
import Loading from 'common/loading';
import { useGlobalContextValue } from 'context';

import Heading from './stats/heading';
import Summary from './stats/summary';

export type StatsType = {
  email: string
  total_sent: number
  open: number
  click: number
}

export const SubscriberStats = () => {
  const match = useRouteMatch<{id: string}>();
  const [stats, setStats] = useState<StatsType|null>(null);
  const [loading, setLoading] = useState(true);
  const contextValue = useGlobalContextValue(window);
  const showError = contextValue.notices.error;

  useEffect(() => {
    MailPoet.Ajax.post({
      api_version: (window as any).mailpoet_api_version,
      endpoint: 'subscriberStats',
      action: 'get',
      data: {
        subscriber_id: match.params.id,
      },
    }).done((response) => {
      setStats(response.data);
      setLoading(false);
    }).fail((response) => {
      setLoading(false);
      if (response.errors.length > 0) {
        showError(
          <>{response.errors.map((error) => <p key={error.message}>{error.message}</p>)}</>,
          { scroll: true }
        );
      }
    });
  }, [match.params.id, showError]);

  if (loading) {
    return (<Loading />);
  }

  return (
    <div>
      <Heading email={stats.email} />
      <div className="mailpoet-subscriber-stats-summary-grid">
        <Summary
          click={stats.click}
          open={stats.open}
          totalSent={stats.total_sent}
        />
      </div>
    </div>
  );
};
