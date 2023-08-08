import { StatsType } from '../types';

export type PropTypes = {
  stats: StatsType;
};

export function WoocommerceRevenues({ stats }: PropTypes): JSX.Element {
  return (
    <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
      <div className="mailpoet-listing">
        <table className="mailpoet-listing-table">
          <tbody>
            <tr>
              <td />
              {stats.periodic_stats.map((periodicStats) => (
                <td key={periodicStats.timeframe}>{periodicStats.timeframe}</td>
              ))}
            </tr>
            <tr>
              <td>Orders created</td>
              {stats.periodic_stats.map((periodicStats) => (
                <td key={periodicStats.timeframe}>
                  {periodicStats.woocommerce.count.toLocaleString()}
                </td>
              ))}
            </tr>
            <tr>
              <td>Total revenue</td>
              {stats.periodic_stats.map((periodicStats) => (
                <td key={periodicStats.timeframe}>
                  {periodicStats.woocommerce.formatted}
                </td>
              ))}
            </tr>
            <tr>
              <td>Average revenue</td>
              {stats.periodic_stats.map((periodicStats) => (
                <td key={periodicStats.timeframe}>
                  {periodicStats.woocommerce.formatted_average}
                </td>
              ))}
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
