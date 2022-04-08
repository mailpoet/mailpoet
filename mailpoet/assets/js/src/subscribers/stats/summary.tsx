import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';
import Tag from 'common/tag/tag';
import Tooltip from 'help-tooltip';
import { ListingsEngagementScore } from '../listings_engagement_score';

export type PropTypes = {
  totalSent: number;
  open: number;
  machineOpen: number;
  click: number;
  subscriber: {
    id: number;
    engagement_score?: number;
  };
};

export default function Summary({
  totalSent,
  open,
  machineOpen,
  click,
  subscriber,
}: PropTypes): JSX.Element {
  let openPercent = 0;
  let machineOpenPercent = 0;
  let clickPercent = 0;
  let notOpenPercent = 0;
  const notOpen = totalSent - (open + machineOpen);
  const displayPercentages = totalSent > 0;
  if (displayPercentages) {
    openPercent = Math.round((open / totalSent) * 100);
    machineOpenPercent = Math.round((machineOpen / totalSent) * 100);
    clickPercent = Math.round((click / totalSent) * 100);
    notOpenPercent = Math.round((notOpen / totalSent) * 100);
  }
  return (
    <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
      <div className="mailpoet-listing">
        <table className="mailpoet-listing-table">
          <tbody>
            <tr>
              <td>{MailPoet.I18n.t('statsSentEmail')}</td>
              <td>
                <b>{totalSent.toLocaleString()}</b>
              </td>
              <td />
            </tr>
            <tr>
              <td>
                <Tag>{MailPoet.I18n.t('statsOpened')}</Tag>
              </td>
              <td>
                <b>{open.toLocaleString()}</b>
              </td>
              <td>{displayPercentages && <>{openPercent}%</>}</td>
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
              <td>
                <b>{machineOpen.toLocaleString()}</b>
              </td>
              <td>{displayPercentages && <>{machineOpenPercent}%</>}</td>
            </tr>
            <tr>
              <td>
                <Tag isInverted>{MailPoet.I18n.t('statsClicked')}</Tag>
              </td>
              <td>
                <b>{click.toLocaleString()}</b>
              </td>
              <td>{displayPercentages && <>{clickPercent}%</>}</td>
            </tr>
            <tr>
              <td>{MailPoet.I18n.t('statsNotClicked')}</td>
              <td>
                <b>{notOpen.toLocaleString()}</b>
              </td>
              <td>{displayPercentages && <>{notOpenPercent}%</>}</td>
            </tr>
            <tr>
              <td>{MailPoet.I18n.t('statisticsColumn')}</td>
              <td>
                <div className="mailpoet-listing-stats">
                  <ListingsEngagementScore
                    id={subscriber.id}
                    engagementScore={subscriber.engagement_score}
                  />
                </div>
              </td>
              <td />
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
