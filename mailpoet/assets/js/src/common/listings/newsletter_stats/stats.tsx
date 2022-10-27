import { MailPoet } from 'mailpoet';
import { Place } from 'react-tooltip';
import { Badge } from './badge';

type StatsBadgeProps = {
  stat: string;
  rate: number;
  tooltipId?: string;
  tooltipPlace?: Place;
  isInverted?: boolean;
};

const stats = {
  opened: {
    badgeRanges: [30, 10, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: [
      MailPoet.I18n.t('openedStatTooltipExcellent'),
      MailPoet.I18n.t('openedStatTooltipGood'),
      MailPoet.I18n.t('openedStatTooltipCritical'),
    ],
  },
  clicked: {
    badgeRanges: [3, 1, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: [
      MailPoet.I18n.t('clickedStatTooltipExcellent'),
      MailPoet.I18n.t('clickedStatTooltipGood'),
      MailPoet.I18n.t('clickedStatTooltipCritical'),
    ],
  },
};

export const getBadgeType = (statName, rate) => {
  const stat = stats[statName] || null;
  if (!stat) {
    return null;
  }

  if (rate < 0 || rate > 100) {
    return null;
  }
  const len = stat.badgeRanges.length;
  for (let i = 0; i < len; i += 1) {
    if (rate > stat.badgeRanges[i]) {
      return stat.badgeTypes[i];
    }
  }
  // rate must be zero at this point
  return stat.badgeTypes[len - 1];
};

function StatsBadge(props: StatsBadgeProps) {
  const badges = {
    excellent: {
      name: MailPoet.I18n.t('excellentBadgeName'),
      tooltipTitle: MailPoet.I18n.t('excellentBadgeTooltip'),
    },
    good: {
      name: MailPoet.I18n.t('goodBadgeName'),
      tooltipTitle: MailPoet.I18n.t('goodBadgeTooltip'),
    },
    critical: {
      name: MailPoet.I18n.t('criticalBadgeName'),
      tooltipTitle: MailPoet.I18n.t('criticalBadgeTooltip'),
    },
  };

  const badgeType = getBadgeType(props.stat, props.rate);
  const badge = badges[badgeType] || null;
  if (!badge) {
    return null;
  }

  const stat = stats[props.stat] || null;
  if (!stat) {
    return null;
  }

  const tooltipId = props.tooltipId || null;
  const tooltipText = (
    <div key={`tooltip-${tooltipId}`}>
      <div className="mailpoet-listing-stats-tooltip-title">
        {badge.tooltipTitle.toUpperCase()}
      </div>
      <div className="mailpoet-listing-stats-tooltip-content">
        <Badge type="excellent" name={badges.excellent.name} />
        {' : '}
        {stat.tooltipText[0]}
        <br />
        <Badge type="good" name={badges.good.name} />
        {' : '}
        {stat.tooltipText[1]}
        <br />
        <Badge type="critical" name={badges.critical.name} />
        {' : '}
        {stat.tooltipText[2]}
      </div>
    </div>
  );

  const content = (
    <Badge
      isInverted={props.isInverted}
      type={badgeType}
      name={badge.name}
      tooltip={tooltipText}
      tooltipId={tooltipId}
      tooltipPlace={props.tooltipPlace}
    />
  );

  return content;
}

StatsBadge.defaultProps = {
  isInverted: true,
};

export { StatsBadge };
