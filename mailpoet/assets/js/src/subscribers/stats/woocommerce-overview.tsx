import { __ } from '@wordpress/i18n';
import { Card, CardBody, Flex, FlexBlock } from '@wordpress/components';
import { StatsType } from '../types';

type Props = {
  stats: StatsType;
};

export function WoocommerceOverview({ stats }: Props): JSX.Element | null {
  const overview = stats.woocommerce_overview;
  if (!overview) {
    return null;
  }

  const tiles = [
    {
      label: __('Orders placed', 'mailpoet'),
      value: (
        <a href={overview.orders_url}>
          {overview.orders_count.toLocaleString()}
        </a>
      ),
    },
    {
      label: __('Lifetime value', 'mailpoet'),
      value: overview.total_revenue_formatted,
    },
    {
      label: __('Average order value', 'mailpoet'),
      value: overview.average_order_value_formatted,
    },
  ];

  return (
    <Flex
      className="mailpoet-subscriber-stats-woocommerce-overview"
      align="stretch"
      gap={4}
    >
      {tiles.map((tile) => (
        <FlexBlock key={tile.label}>
          <Card size="medium">
            <CardBody>
              <div className="mailpoet-subscriber-stats-woocommerce-overview-label">
                {tile.label}
              </div>
              <div className="mailpoet-subscriber-stats-woocommerce-overview-value">
                {tile.value}
              </div>
            </CardBody>
          </Card>
        </FlexBlock>
      ))}
    </Flex>
  );
}

WoocommerceOverview.displayName = 'WoocommerceOverview';
