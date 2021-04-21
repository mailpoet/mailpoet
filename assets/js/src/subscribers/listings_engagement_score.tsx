import React from 'react';
import Badge from '../common/listings/newsletter_stats/badge';
import MailPoet from '../mailpoet';

interface Props {
  subscriber: {
    id: string;
    engagement_score?: number;
  };
}

export const ListingsEngagementScore: React.FunctionComponent<Props> = ({ subscriber }) => {
  const badges = {
    unknown: {
      name: MailPoet.I18n.t('unknownBadgeName'),
      type: 'unknown' as const,
      tooltipTitle: MailPoet.I18n.t('unknownBadgeTooltip'),
      tooltipText: MailPoet.I18n.t('tooltipUnknown'),
    },
    excellent: {
      name: MailPoet.I18n.t('excellentBadgeName'),
      type: 'excellent' as const,
      tooltipTitle: MailPoet.I18n.t('excellentBadgeTooltip'),
      tooltipText: MailPoet.I18n.t('tooltipExcellent'),
    },
    good: {
      name: MailPoet.I18n.t('goodBadgeName'),
      type: 'good' as const,
      tooltipTitle: MailPoet.I18n.t('goodBadgeTooltip'),
      tooltipText: MailPoet.I18n.t('tooltipGood'),
    },
    average: {
      name: MailPoet.I18n.t('averageBadgeName'),
      type: 'average' as const,
      tooltipTitle: MailPoet.I18n.t('averageBadgeTooltip'),
      tooltipText: MailPoet.I18n.t('tooltipAverage'),
    },
  };
  const tooltipId = `badge-${subscriber.id}`;
  let badge;
  if (subscriber.engagement_score == null) {
    badge = badges.unknown;
  } else if (subscriber.engagement_score < 20) {
    badge = badges.average;
  } else if (subscriber.engagement_score < 50) {
    badge = badges.good;
  } else {
    badge = badges.excellent;
  }
  const tooltipText = (
    <div key={`tooltip-${tooltipId}`}>
      <div className="mailpoet-listing-stats-tooltip-title">
        {badge.tooltipTitle.toUpperCase()}
      </div>
      <div className="mailpoet-listing-stats-tooltip-content">
        <Badge
          type="unknown"
          name={MailPoet.I18n.t('unknownBadgeName')}
        />
        {' : '}
        {badges.unknown.tooltipText}
        <br />
        <Badge
          type="excellent"
          name={MailPoet.I18n.t('excellentBadgeName')}
        />
        {' : '}
        {badges.excellent.tooltipText}
        <br />
        <Badge
          type="good"
          name={MailPoet.I18n.t('goodBadgeName')}
        />
        {' : '}
        {badges.good.tooltipText}
        <br />
        <Badge
          type="average"
          name={MailPoet.I18n.t('averageBadgeName')}
        />
        {' : '}
        {badges.average.tooltipText}
      </div>
    </div>
  );
  return (
    <div className="mailpoet-listing-stats">
      <div className="mailpoet-listing-stats-opened-clicked">
        {subscriber.engagement_score != null && (
          <div className="mailpoet-listing-stats-percentages">
            {
              subscriber
                .engagement_score
                .toLocaleString(
                  undefined,
                  {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }
                )
            }
            %
          </div>
        )}
        <div>
          <Badge
            isInverted
            type={badge.type}
            name={badge.name}
            tooltip={tooltipText}
            tooltipId={tooltipId}
            tooltipPlace="top"
          />
        </div>
      </div>
    </div>
  );
};
