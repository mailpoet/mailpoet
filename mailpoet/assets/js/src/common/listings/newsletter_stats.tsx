import { ReactNode } from 'react';
import { MailPoet } from 'mailpoet';
import { StatsBadge } from './newsletter_stats/stats';
import { Tooltip } from '../tooltip/tooltip';
import { Tag } from '../tag/tag';

type NewsletterStatsProps = {
  opened: number;
  clicked: number;
  revenues?: string;
  hideBadges?: boolean;
  newsletterId?: number; // used for tooltip IDs
  wrapContentInLink?: (content: ReactNode, idPrefix: string) => JSX.Element;
};

export function NewsletterStats({
  opened,
  clicked,
  revenues,
  hideBadges,
  newsletterId,
  wrapContentInLink,
}: NewsletterStatsProps) {
  // format to 1 decimal place
  const openedDisplay = MailPoet.Num.toLocaleFixed(opened, 1);
  const clickedDisplay = MailPoet.Num.toLocaleFixed(clicked, 1);

  let clickedAndOpenedStats = (
    <div className="mailpoet-listing-stats-opened-clicked">
      <div className="mailpoet-listing-stats-percentages">
        {clickedDisplay}
        %
        <br />
        <span className="mailpoet-listing-stats-percentages-opens">
          {openedDisplay}%
        </span>
      </div>
      {!hideBadges && (
        <div>
          <StatsBadge
            stat="clicked"
            rate={clicked}
            tooltipId={`clicked-${newsletterId || '0'}`}
          />
          <br />
          <StatsBadge
            stat="opened"
            rate={opened}
            tooltipId={`opened-${newsletterId || '0'}`}
          />
        </div>
      )}
    </div>
  );

  let revenueStats: ReactNode = null;
  if (revenues) {
    const revenuesTooltipId = `revenues-${newsletterId || '0'}`;
    revenueStats = (
      <div>
        <Tag data-tip data-for={revenuesTooltipId}>
          {revenues}
        </Tag>
        <Tooltip place="top" multiline id={revenuesTooltipId}>
          <div className="mailpoet-listing-stats-tooltip-content">
            {MailPoet.I18n.t('revenueStatsTooltipShort')}
          </div>
        </Tooltip>
      </div>
    );
  }

  if (wrapContentInLink) {
    clickedAndOpenedStats = wrapContentInLink(
      clickedAndOpenedStats,
      'opened-and-clicked',
    );
    revenueStats = wrapContentInLink(revenueStats, 'revenue');
  }

  return (
    <div className="mailpoet-listing-stats">
      {clickedAndOpenedStats}
      {revenueStats}
    </div>
  );
}
