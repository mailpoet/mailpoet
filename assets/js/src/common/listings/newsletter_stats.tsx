import React from 'react';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import StatsBadge from './newsletter_stats/stats';
import Tooltip from '../tooltip/tooltip';

type NewsletterStatsProps = {
  opened: number,
  clicked: number,
  revenues?: string,
  hideBadges?: boolean,
  newsletterId?: number, // used for tooltip IDs
  wrapContentInLink?: (content: React.ReactNode, idPrefix: string) => any,
}

const NewsletterStats = ({
  opened,
  clicked,
  revenues,
  hideBadges,
  newsletterId,
  wrapContentInLink,
}: NewsletterStatsProps) => {
  // format to 1 decimal place
  const clickedDisplay = MailPoet.Num.toLocaleFixed(opened, 1);
  const openedDisplay = MailPoet.Num.toLocaleFixed(clicked, 1);

  let openedAndClickedStats = (
    <div className="mailpoet-listing-stats-opened-clicked">
      <div className="mailpoet-listing-stats-percentages">
        {openedDisplay}
        %
        <br />
        {clickedDisplay}
        %
      </div>
      {!hideBadges && (
        <div className="mailpoet-listing-stats-badges">
          <StatsBadge
            stat="opened"
            rate={opened}
            tooltipId={`opened-${newsletterId || '0'}`}
          />
          <br />
          <StatsBadge
            stat="clicked"
            rate={clicked}
            tooltipId={`clicked-${newsletterId || '0'}`}
          />
        </div>
      )}
    </div>
  );

  let revenueStats = null;
  if (revenues) {
    const revenuesTooltipId = `revenues-${newsletterId || '0'}`;
    revenueStats = (
      <div>
        <span
          className="mailpoet-listing-stats-revenues"
          data-tip
          data-for={revenuesTooltipId}
        >
          {revenues}
        </span>
        <Tooltip
          place="top"
          multiline
          id={revenuesTooltipId}
        >
          <div className="mailpoet-listing-stats-tooltip-content">
            {MailPoet.I18n.t('revenueStatsTooltipShort')}
          </div>
        </Tooltip>
      </div>
    );
  }

  if (wrapContentInLink) {
    openedAndClickedStats = wrapContentInLink(openedAndClickedStats, 'opened-and-clicked');
    revenueStats = wrapContentInLink(revenueStats, 'revenue');
  }

  return (
    <div className="mailpoet-listing-stats">
      {openedAndClickedStats}
      {revenueStats}
    </div>
  );
};

export default NewsletterStats;
