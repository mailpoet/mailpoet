import { __ } from '@wordpress/i18n';
import { PlacesType } from 'react-tooltip';
import { Badge } from './badge';

type StatsBadgeProps = {
  stat: string;
  rate: number;
  tooltipId?: string;
  tooltipPlace?: PlacesType;
  isInverted?: boolean;
};

const getStats = () => ({
  opened: {
    badgeRanges: [30, 10, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: {
      // translators: Excellent open rate
      excellent: __('above 30%', 'mailpoet'),
      // translators: Good open rate
      good: __('between 10 and 30%', 'mailpoet'),
      // translators: Critical open rate
      critical: __('under 10%', 'mailpoet'),
    },
  },
  clicked: {
    badgeRanges: [3, 1, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: {
      // translators: Excellent click rate
      excellent: __('above 3%', 'mailpoet'),
      // translators: Good click rate
      good: __('between 1 and 3%', 'mailpoet'),
      // translators: Critical click rate
      critical: __('under 1%', 'mailpoet'),
    },
  },
  bounced: {
    badgeRanges: [1.5, 0.5, 0],
    badgeTypes: ['critical', 'good', 'excellent'],
    tooltipText: {
      // translators: Excellent bounce rate
      excellent: __('below 0.5%', 'mailpoet'),
      // translators: Good bounce rate
      good: __('between 0.5% and 1.5%', 'mailpoet'),
      // translators: Critical bounce rate
      critical: __('above 1.5%', 'mailpoet'),
    },
  },
  unsubscribed: {
    badgeRanges: [0.7, 0.3, 0],
    badgeTypes: ['critical', 'good', 'excellent'],
    tooltipText: {
      // translators: Excellent unsubscribe rate
      excellent: __('Below 0.3%', 'mailpoet'),
      // translators: Good unsubscribe rate
      good: __('between 0.3% and 0.7%', 'mailpoet'),
      // translators: Critical unsubscribe rate
      critical: __('above 0.7%', 'mailpoet'),
    },
  },
});

export const getBadgeType = (statName, rate) => {
  const stat = getStats()[statName] || null;
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

  const stat = getStats()[props.stat] || null;
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
