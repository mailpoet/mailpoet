import React from 'react';

interface Props {
  subscriber: {
    engagement_score?: number;
  };
}

export const ListingsEngagementScore: React.FunctionComponent<Props> = ({ subscriber }) => (
  <div className="mailpoet-listing-stats-opened-clicked">
    {subscriber.engagement_score != null && (
      <div className="mailpoet-listing-stats-percentages">
        {subscriber.engagement_score}
        %
      </div>
    )}
  </div>
);
