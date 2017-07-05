import MailPoet from 'mailpoet'
import React from 'react'

import Badge from './badge.jsx'

const badges = {
  excellent: {
    name: MailPoet.I18n.t('excellentBadgeName'),
    tooltipTitle: MailPoet.I18n.t('excellentBadgeTooltip')
  },
  good: {
    name: MailPoet.I18n.t('goodBadgeName'),
    tooltipTitle: MailPoet.I18n.t('goodBadgeTooltip')
  },
  bad: {
    name: MailPoet.I18n.t('badBadgeName'),
    tooltipTitle: MailPoet.I18n.t('badBadgeTooltip')
  }
};

const stats = {
  opened: {
    badgeRanges: [30, 10, 0],
    badgeTypes: [
      'excellent',
      'good',
      'bad'
    ],
    tooltipText: MailPoet.I18n.t('openedStatTooltip'),
  },
  clicked: {
    badgeRanges: [3, 1, 0],
    badgeTypes: [
      'excellent',
      'good',
      'bad'
    ],
    tooltipText: MailPoet.I18n.t('clickedStatTooltip')
  },
  unsubscribed: {
    badgeRanges: [3, 1, 0],
    badgeTypes: [
      'bad',
      'good',
      'excellent'
    ],
    tooltipText: MailPoet.I18n.t('unsubscribedStatTooltip')
  },
};

class StatsBadge extends React.Component {
  getBadgeType(stat, rate) {
    const len = stat.badgeRanges.length;
    for (var i = 0; i < len; i += 1) {
      if (rate > stat.badgeRanges[i]) {
        return stat.badgeTypes[i];
      }
    }
    // rate must be zero at this point
    return stat.badgeTypes[len - 1];
  }
  render() {
    const stat = stats[this.props.stat] || null;
    if (!stat) {
      return null;
    }

    const rate = this.props.rate;
    if (rate < 0 || rate > 100) {
      return null;
    }

    const badgeType = this.getBadgeType(stat, rate);
    const badge = badges[badgeType] || null;
    if (!badge) {
      return null;
    }

    const tooltipText = `${badge.tooltipTitle}\n\n${stat.tooltipText}`;
    const tooltipId = this.props.tooltipId || null;

    const content = (
      <Badge
        type={badgeType}
        name={badge.name}
        tooltip={tooltipText}
        tooltipId={tooltipId}
      />
    );

    if (this.props.headline) {
      return (
        <div>
          <span className={`mailpoet_stat_${badgeType}`}>
            {this.props.headline}
          </span> {content}
        </div>
      );
    }

    return content;
  }
}

export default StatsBadge;
