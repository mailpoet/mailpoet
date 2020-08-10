import React from 'react';
import MailPoet from 'mailpoet';
import Tag from 'common/tag/tag';

export type PropTypes = {
  count: number
  revenueValue: string
  averageRevenueValue: string
}

export default ({ revenueValue, count, averageRevenueValue }: PropTypes) => (
  <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
    <div className="mailpoet-listing">
      <table className="mailpoet-listing-table wp-list-table widefat">
        <tbody>
          <tr>
            <td>Orders created</td>
            <td><b>{count.toLocaleString()}</b></td>
          </tr>
          <tr>
            <td>Total revenue</td>
            <td><b>{revenueValue}</b></td>
          </tr>
          <tr>
            <td>Average revenue</td>
            <td><b>{averageRevenueValue}</b></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
);
