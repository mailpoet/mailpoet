import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TableCard } from '@woocommerce/components';
import { Hooks } from 'wp-js-hooks';
import { storeName, SubscriberSection } from '../../../store';
import { transformSubscribersToRows } from './rows';
import { Upgrade } from '../orders/upgrade';

const headers = [
  {
    key: 'last_name',
    isSortable: true,
    label: __('Subscriber', 'mailpoet'),
  },
  {
    key: 'step',
    isSortable: true,
    label: __('Automation step', 'mailpoet'),
  },
  {
    key: 'status',
    isSortable: true,
    label: __('Status', 'mailpoet'),
  },
  {
    key: 'updated_at',
    isSortable: true,
    label: __('Updated on', 'mailpoet'),
  },
  {
    key: 'action',
    isSortable: false,
    label: null,
  },
];

export function Subscribers(): JSX.Element {
  const { subscriberSection } = useSelect((s) => ({
    subscriberSection: s(storeName).getSection(
      'subscribers',
    ) as SubscriberSection,
  }));

  const rows = transformSubscribersToRows(subscriberSection.data);

  const beforeTable = Hooks.applyFilters(
    'mailpoet_analytics_subscribers_before_table',
    <Upgrade />,
  ) as null | JSX.Element;

  return (
    <div className="mailpoet-analytics-subscribers">
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
                subscriberSection.customQuery.order_by === param &&
                subscriberSection.customQuery.order === 'asc'
                  ? 'desc'
                  : 'asc',
            };
          }
          void dispatch(storeName).updateSection({
            ...subscriberSection,
            customQuery: {
              ...subscriberSection.customQuery,
              ...customQuery,
            },
          });
        }}
        query={{
          paged: subscriberSection.customQuery.page,
          orderby: subscriberSection.customQuery.order_by,
          order: subscriberSection.customQuery.order,
        }}
        rows={rows}
        headers={headers}
        showMenu={false}
        rowsPerPage={subscriberSection.customQuery.limit}
        totalRows={
          subscriberSection.data !== undefined
            ? subscriberSection.data.results
            : 0
        }
        isLoading={subscriberSection.data?.items === undefined}
      />
    </div>
  );
}
