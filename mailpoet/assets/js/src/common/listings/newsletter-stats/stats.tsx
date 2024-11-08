import { __, sprintf } from '@wordpress/i18n';
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
      // translators: Shows a percentage range, "above 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      excellent: sprintf(__('above %s%%', 'mailpoet'), 30),
      // translators: Shows a percentage range, "between 10% and 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      good: sprintf(__('between %s%% and %s%%', 'mailpoet'), 10, 30),
      // translators: Shows a percentage range, "below 10%". Used in contexts like open, click, bounce, or unsubscribe rates.
      critical: sprintf(__('below %s%%', 'mailpoet'), 10),
    },
  },
  clicked: {
    badgeRanges: [3, 1, 0],
    badgeTypes: ['excellent', 'good', 'critical'],
    tooltipText: {
      // translators: Shows a percentage range, "above 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      excellent: sprintf(__('above %s%%', 'mailpoet'), 3),
      // translators: Shows a percentage range, "between 10% and 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      good: sprintf(__('between %s%% and %s%%', 'mailpoet'), 1, 3),
      // translators: Shows a percentage range, "below 10%". Used in contexts like open, click, bounce, or unsubscribe rates.
      critical: sprintf(__('below %s%%', 'mailpoet'), 1),
    },
  },
  bounced: {
    badgeRanges: [1.5, 0.5, 0],
    badgeTypes: ['critical', 'good', 'excellent'],
    tooltipText: {
      // translators: Shows a percentage range, "below 10%". Used in contexts like open, click, bounce, or unsubscribe rates.
      excellent: sprintf(__('below %s%%', 'mailpoet'), 0.5),
      // translators: Shows a percentage range, "between 10% and 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      good: sprintf(__('between %s%% and %s%%', 'mailpoet'), 0.5, 1.5),
      // translators: Shows a percentage range, "above 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      critical: sprintf(__('above %s%%', 'mailpoet'), 1.5),
    },
  },
  unsubscribed: {
    badgeRanges: [0.7, 0.3, 0],
    badgeTypes: ['critical', 'good', 'excellent'],
    tooltipText: {
      // translators: Shows a percentage range, "below 10%". Used in contexts like open, click, bounce, or unsubscribe rates.
      excellent: sprintf(__('below %s%%', 'mailpoet'), 0.3),
      // translators: Shows a percentage range, "between 10% and 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      good: sprintf(__('between %s%% and %s%%', 'mailpoet'), 0.3, 0.7),
      // translators: Shows a percentage range, "above 30%". Used in contexts like open, click, bounce, or unsubscribe rates.
      critical: sprintf(__('above %s%%', 'mailpoet'), 0.7),
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
