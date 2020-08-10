import React from 'react';
import MailPoet from 'mailpoet';
import Tag from 'common/tag/tag';

export type PropTypes = {
  totalSent: number
  open: number
  click: number
}

export default ({ totalSent, open, click }: PropTypes) => {
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
        <table className="mailpoet-listing-table wp-list-table widefat">
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
          </tbody>
        </table>
      </div>
    </div>
  );
};
