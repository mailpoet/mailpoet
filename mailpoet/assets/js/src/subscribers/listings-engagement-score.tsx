import { Badge } from '../common/listings/newsletter-stats/badge';
import { MailPoet } from '../mailpoet';

interface Props {
  id: number;
  engagementScore?: number;
}

export type EngagementScoreBadgeType =
  | 'unknown'
  | 'average'
  | 'good'
  | 'excellent';

export function getEngagementScoreBadgeType(
  engagementScore?: number,
): EngagementScoreBadgeType {
  if (engagementScore == null) {
    return 'unknown';
  }
  if (engagementScore < 20) {
    return 'average';
  }
  if (engagementScore < 50) {
    return 'good';
  }
  return 'excellent';
}

export function ListingsEngagementScore({
  id,
  engagementScore,
}: Props): JSX.Element {
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
  const tooltipId = `badge-${id}`;
  const badge = badges[getEngagementScoreBadgeType(engagementScore)];
  const tooltipText = (
    <div key={`tooltip-${tooltipId}`}>
      <div className="mailpoet-listing-stats-tooltip-title">
        {badge.tooltipTitle.toUpperCase()}
      </div>
      <div className="mailpoet-listing-stats-tooltip-description">
        {MailPoet.I18n.t('engagementScoreDescription')}
      </div>
      <div className="mailpoet-listing-stats-tooltip-content">
        <Badge type="unknown" name={MailPoet.I18n.t('unknownBadgeName')} />
        {' : '}
        {badges.unknown.tooltipText}
        <br />
        <Badge type="excellent" name={MailPoet.I18n.t('excellentBadgeName')} />
        {' : '}
        {badges.excellent.tooltipText}
        <br />
        <Badge type="good" name={MailPoet.I18n.t('goodBadgeName')} />
        {' : '}
        {badges.good.tooltipText}
        <br />
        <Badge type="average" name={MailPoet.I18n.t('averageBadgeName')} />
        {' : '}
        {badges.average.tooltipText}
      </div>
    </div>
  );
  return (
    <div className="mailpoet-listing-stats-opened-clicked">
      {engagementScore != null && (
        <div className="mailpoet-listing-stats-percentages">
          {engagementScore.toLocaleString(undefined, {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
          })}
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
  );
}
