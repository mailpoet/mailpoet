import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TableCard } from '@woocommerce/components';
import { Hooks } from 'wp-js-hooks';
import { OrderSection, storeName } from '../../../store';
import { transformOrdersToRows } from './rows';
import { calculateSummary } from './summary';
import { Upgrade } from './upgrade';

const headers = [
  {
    key: 'created_at',
    isSortable: true,
    label: __('Date', 'mailpoet'),
  },
  {
    key: 'id',
    label: __('Order #', 'mailpoet'),
  },
  {
    key: 'last_name',
    isSortable: true,
    label: __('Customer', 'mailpoet'),
  },
  {
    key: 'items',
    label: __('Product(s)', 'mailpoet'),
  },
  {
    key: 'subject',
    isSortable: true,
    label: __('Email clicked', 'mailpoet'),
  },
  {
    key: 'status',
    isSortable: true,
    label: __('Status', 'mailpoet'),
  },
  {
    key: 'revenue',
    isSortable: true,
    label: __('Revenue', 'mailpoet'),
  },
];

export function Orders(): JSX.Element {
  const { ordersSection } = useSelect((s) => ({
    ordersSection: s(storeName).getSection('orders') as OrderSection,
  }));

  const orders = ordersSection?.data?.items;
  const rows = transformOrdersToRows(ordersSection?.data);
  const summary = calculateSummary(orders ?? []);
  const beforeTable = Hooks.applyFilters(
    'mailpoet_analytics_orders_before_table',
    <Upgrade />,
  ) as null | JSX.Element;

  return (
    <div className="mailpoet-analytics-orders">
      {beforeTable}
      <TableCard
        title=""
        onQueryChange={(type: string) => (param: unknown) => {
          let customQuery = {};
          if (type === 'paged') {
            customQuery = { page: param };
          } else if (type === 'per_page') {
            customQuery = {
              page: 1,
              limit: param,
            };
          } else if (type === 'sort') {
            customQuery = {
              page: 1,
              order_by: param,
              order:
                ordersSection.customQuery.order_by === param &&
                ordersSection.customQuery.order === 'asc'
                  ? 'desc'
                  : 'asc',
            };
          }
          void dispatch(storeName).updateSection({
            ...ordersSection,
            customQuery: {
              ...ordersSection.customQuery,
              ...customQuery,
            },
          });
        }}
        query={{
          paged: ordersSection.customQuery.page,
          orderby: ordersSection.customQuery.order_by,
          order: ordersSection.customQuery.order,
        }}
        rows={rows}
        headers={headers}
        showMenu={false}
        rowsPerPage={ordersSection.customQuery.limit}
        totalRows={
          ordersSection.data !== undefined ? ordersSection.data.results : 0
        }
        summary={summary}
        isLoading={orders === undefined}
      />
    </div>
  );
}
