import ReactStringReplace from 'react-string-replace';
import { MailPoet } from 'mailpoet';
import { Tag } from 'common/tag/tag';
import { Tooltip } from 'help-tooltip';
import { ListingsEngagementScore } from '../listings_engagement_score';
import { PeriodicStats, StatsType } from '../types';

export type PropTypes = {
  stats: StatsType;
  subscriber: {
    id: number;
    engagement_score?: number;
  };
};

export function Summary({ stats, subscriber }: PropTypes): JSX.Element {
  return (
    <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
      <div className="mailpoet-listing">
        <table className="mailpoet-listing-table">
          <thead>
            <tr>
              <td />
              {stats.periodic_stats.map(
                (periodicStats: PeriodicStats): JSX.Element => (
                  <td key={periodicStats.timeframe}>
                    {periodicStats.timeframe}
                  </td>
                ),
              )}
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{MailPoet.I18n.t('statsSentEmail')}</td>
              {stats.periodic_stats.map(
                (periodicStats: PeriodicStats): JSX.Element => (
                  <td key={periodicStats.timeframe}>
                    {periodicStats.total_sent}
                  </td>
                ),
              )}
            </tr>
            <tr>
              <td>
                <Tag>{MailPoet.I18n.t('statsOpened')}</Tag>
              </td>
              {stats.periodic_stats.map(
                (periodicStats: PeriodicStats): JSX.Element => {
                  const displayPercentage = periodicStats.total_sent > 0;
                  let cell = periodicStats.open.toLocaleString();
                  if (displayPercentage) {
                    const percentage = Math.round(
                      (periodicStats.open / periodicStats.total_sent) * 100,
                    );
                    cell += ` (${percentage}%)`;
                  }
                  return <td key={periodicStats.timeframe}>{cell}</td>;
                },
              )}
            </tr>
            <tr>
              <td>
                <Tag>{MailPoet.I18n.t('statsMachineOpened')}</Tag>
                <Tooltip
                  tooltip={ReactStringReplace(
                    MailPoet.I18n.t('statsMachineOpenedTooltip'),
                    /\[link](.*?)\[\/link]/,
                    (match) => (
                      <span
                        style={{ pointerEvents: 'all' }}
                        key="machine-opened-info"
                      >
                        <a
                          href="https://kb.mailpoet.com/article/368-what-are-machine-opens"
                          key="kb-link"
                          target="_blank"
                          data-beacon-article="6124b7fb21ef206e5592e188"
                          rel="noopener noreferrer"
                        >
                          {match}
                        </a>
                      </span>
                    ),
                  )}
                />
              </td>
              {stats.periodic_stats.map(
                (periodicStats: PeriodicStats): JSX.Element => {
                  const displayPercentage = periodicStats.total_sent > 0;
                  let cell = periodicStats.machine_open.toLocaleString();
                  if (displayPercentage) {
                    const percentage = Math.round(
                      (periodicStats.machine_open / periodicStats.total_sent) *
                        100,
                    );
                    cell += ` (${percentage}%)`;
                  }
                  return <td key={periodicStats.timeframe}>{cell}</td>;
                },
              )}
            </tr>
            <tr>
              <td>
                <Tag isInverted>{MailPoet.I18n.t('statsClicked')}</Tag>
              </td>
              {stats.periodic_stats.map(
                (periodicStats: PeriodicStats): JSX.Element => {
                  const displayPercentage = periodicStats.total_sent > 0;
                  let cell = periodicStats.click.toLocaleString();
                  if (displayPercentage) {
                    const percentage = Math.round(
                      (periodicStats.click / periodicStats.total_sent) * 100,
                    );
                    cell += ` (${percentage}%)`;
                  }
                  return <td key={periodicStats.timeframe}>{cell}</td>;
                },
              )}
            </tr>
            <tr>
              <td>{MailPoet.I18n.t('statsNotClicked')}</td>
              {stats.periodic_stats.map(
                (periodicStats: PeriodicStats): JSX.Element => {
                  const notOpen =
                    periodicStats.total_sent -
                    (periodicStats.open + periodicStats.machine_open);
                  const displayPercentage = periodicStats.total_sent > 0;
                  let cell = notOpen.toLocaleString();
                  if (displayPercentage) {
                    const percentage = Math.round(
                      (notOpen / periodicStats.total_sent) * 100,
                    );
                    cell += ` (${percentage}%)`;
                  }
                  return <td key={periodicStats.timeframe}>{cell}</td>;
                },
              )}
            </tr>
            <tr>
              <td>{MailPoet.I18n.t('statisticsColumn')}</td>
              <td colSpan={stats.periodic_stats.length}>
                <div className="mailpoet-listing-stats">
                  <ListingsEngagementScore
                    id={subscriber.id}
                    engagementScore={subscriber.engagement_score}
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
