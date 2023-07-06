import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TableCard } from '@woocommerce/components/build';
import { MailPoet } from '../../../../../../../mailpoet';
import { storeName, SubscriberSection } from '../../../store';
import { transformSubscribersToRows } from './rows';
import { Upgrade } from '../orders/upgrade';
import { Filter } from './filter';

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
];

export function Subscribers(): JSX.Element {
  const { subscriberSection } = useSelect((s) => ({
    subscriberSection: s(storeName).getSection(
      'subscribers',
    ) as SubscriberSection,
  }));

  const subscribers =
    subscriberSection.data !== undefined
      ? subscriberSection.data.items
      : undefined;
  const rows = transformSubscribersToRows(subscribers);

  return (
    <div className="mailpoet-analytics-subscribers">
      {!MailPoet.premiumActive && (
        <Upgrade
          text={
            <span>
              <strong>{__("You're viewing sample data.", 'mailpoet')}</strong>
              &nbsp;
              {__(
                'To use data from your email activity, upgrade to a premium plan.',
                'mailpoet',
              )}
            </span>
          }
        />
      )}

      {MailPoet.premiumActive && <Filter />}

      <TableCard
        title=""
        caption=""
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
          dispatch(storeName).updateSection({
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
        onRowClick={() => {}}
        totalRows={
          subscriberSection.data !== undefined
            ? subscriberSection.data.results
            : 0
        }
        isLoading={subscribers === undefined}
      />
    </div>
  );
}
