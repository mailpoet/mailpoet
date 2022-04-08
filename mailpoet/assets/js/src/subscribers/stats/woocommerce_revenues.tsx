export type PropTypes = {
  count: number;
  revenueValue: string;
  averageRevenueValue: string;
};

export default function WoocommerceRevenues({
  revenueValue,
  count,
  averageRevenueValue,
}: PropTypes): JSX.Element {
  return (
    <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
      <div className="mailpoet-listing">
        <table className="mailpoet-listing-table">
          <tbody>
            <tr>
              <td>Orders created</td>
              <td>
                <b>{count.toLocaleString()}</b>
              </td>
            </tr>
            <tr>
              <td>Total revenue</td>
              <td>
                <b>{revenueValue}</b>
              </td>
            </tr>
            <tr>
              <td>Average revenue</td>
              <td>
                <b>{averageRevenueValue}</b>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
