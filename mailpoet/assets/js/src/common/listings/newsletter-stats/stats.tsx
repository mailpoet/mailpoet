import { __, _x } from '@wordpress/i18n';
import { PlacesType } from 'react-tooltip';
import { Badge } from './badge';

type StatsBadgeProps = {
  stat: string;
  rate: number;
  tooltipId?: string;
  tooltipPlace?: PlacesType;
  isInverted?: boolean;
};

const stats = {
  opened: {
    badgeRanges: [30, 10, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: {
      excellent: _x('above 30%', 'Excellent open rate', 'mailpoet'),
      good: _x('between 10 and 30%', 'Good open rate', 'mailpoet'),
      critical: _x('under 10%', 'Critical open rate', 'mailpoet'),
    },
  },
  clicked: {
    badgeRanges: [3, 1, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: {
      excellent: _x('above 3%', 'Excellent click rate', 'mailpoet'),
      good: _x('between 1 and 3%', 'Good click rate', 'mailpoet'),
      critical: _x('under 1%', 'Critical click rate', 'mailpoet'),
    },
  },
  bounced: {
    badgeRanges: [1.5, 0.5, 0],
    badgeTypes: ['critical', 'good', 'excellent'],
    tooltipText: {
      excellent: _x('below 0.5%', 'Excellent bounce rate', 'mailpoet'),
      good: _x('between 0.5% and 1.5%', 'Good bounce rate', 'mailpoet'),
      critical: _x('above 1.5%', 'Critical bounce rate', 'mailpoet'),
    },
  },
  unsubscribed: {
    badgeRanges: [0.7, 0.3, 0],
    badgeTypes: ['critical', 'good', 'excellent'],
    tooltipText: {
      excellent: _x('Below 0.3%', 'Excellent unsubscribe rate', 'mailpoet'),
      good: _x('between 0.3% and 0.7%', 'Good unsubscribe rate', 'mailpoet'),
      critical: _x('above 0.7%', 'Critical unsubscribe rate', 'mailpoet'),
    },
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
  const { isInverted = true } = props;
  const badges = {
    excellent: {
      name: __('Excellent', 'mailpoet'),
      tooltipTitle: __('Congrats!', 'mailpoet'),
    },
    good: {
      name: __('Good', 'mailpoet'),
      tooltipTitle: __('Good stuff.', 'mailpoet'),
    },
    critical: {
      name: __('Critical', 'mailpoet'),
      tooltipTitle: __('Something to improve.', 'mailpoet'),
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
        {stat.tooltipText.excellent}
        <br />
        <Badge type="good" name={badges.good.name} />
        {' : '}
        {stat.tooltipText.good}
        <br />
        <Badge type="critical" name={badges.critical.name} />
        {' : '}
        {stat.tooltipText.critical}
      </div>
    </div>
  );

  const content = (
    <Badge
      isInverted={isInverted}
      type={badgeType}
      name={badge.name}
      tooltip={tooltipText}
      tooltipId={tooltipId}
      tooltipPlace={props.tooltipPlace}
    />
  );

  return content;
}

export { StatsBadge };
