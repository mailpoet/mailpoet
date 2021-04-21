import React from 'react';
import MailPoet from 'mailpoet';
import Tag from 'common/tag/tag';
import { ListingsEngagementScore } from '../listings_engagement_score';

export type PropTypes = {
  totalSent: number;
  open: number;
  click: number;
  subscriber: {
    id: string;
    engagement_score?: number;
  };
}

export default ({
  totalSent,
  open,
  click,
  subscriber,
}: PropTypes): JSX.Element => {
  let openPercent = 0;
  let clickPercent = 0;
  let notOpenPercent = 0;
  const displayPercentages = (totalSent > 0);
  if (displayPercentages) {
    openPercent = Math.round((open / totalSent) * 100);
    clickPercent = Math.round((click / totalSent) * 100);
    notOpenPercent = Math.round(((totalSent - open) / totalSent) * 100);
  }
  return (
    <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
      <div className="mailpoet-listing">
        <table className="mailpoet-listing-table">
          <tbody>
            <tr>
              <td>{MailPoet.I18n.t('statsSentEmail')}</td>
              <td><b>{totalSent.toLocaleString()}</b></td>
              <td />
            </tr>
            <tr>
              <td>
                <Tag>{MailPoet.I18n.t('statsOpened')}</Tag>
              </td>
              <td><b>{open.toLocaleString()}</b></td>
              <td>
                {displayPercentages
                  && (
                    <>
                      {openPercent}
                      %
                    </>
                  )}
              </td>
            </tr>
            <tr>
              <td>
                <Tag isInverted>{MailPoet.I18n.t('statsClicked')}</Tag>
              </td>
              <td><b>{click.toLocaleString()}</b></td>
              <td>
                {displayPercentages
                && (
                  <>
                    {clickPercent}
                    %
                  </>
                )}
              </td>
            </tr>
            <tr>
              <td>{MailPoet.I18n.t('statsNotClicked')}</td>
              <td><b>{(totalSent - open).toLocaleString()}</b></td>
              <td>
                {displayPercentages
                && (
                  <>
                    {notOpenPercent}
                    %
                  </>
                )}
              </td>
            </tr>
            <tr>
              <td>{MailPoet.I18n.t('statisticsColumn')}</td>
              <td>
                <div className="mailpoet-listing-stats">
                  <ListingsEngagementScore subscriber={subscriber} />
                </div>
              </td>
              <td />
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
};
