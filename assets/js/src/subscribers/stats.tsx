import React, { useEffect, useState } from 'react';
import {
  useRouteMatch,
  useLocation,
} from 'react-router-dom';
import MailPoet from 'mailpoet';
import Loading from 'common/loading';
import { useGlobalContextValue } from 'context';

import Heading from './stats/heading';
import Summary from './stats/summary';
import WoocommerceRevenues from './stats/woocommerce_revenues';
import OpenedEmailsStats from './stats/opened_email_stats';

export type StatsType = {
  email: string
  total_sent: number
  open: number
  click: number
  woocommerce: {
    currency: string
    value: number
    count: number
    formatted: string
    formatted_average: string
  }
}

export const SubscriberStats = () => {
  const match = useRouteMatch<{id: string}>();
  const location = useLocation();
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
};
