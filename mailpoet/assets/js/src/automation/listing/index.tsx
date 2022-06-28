import { Search, TableCard } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { getRow } from './get-row';
import { Workflow } from './workflow';

interface AutomationListingProps {
  workflows: Workflow[];
  loading: boolean;
}

export function AutomationListing({
  workflows,
  loading,
}: AutomationListingProps): JSX.Element {
  const rows = workflows.map((workflow) => getRow(workflow));

  const headers = [
    { key: 'name', label: __('Name', 'mailpoet') },
    { key: 'subscribers', label: __('Subscribers', 'mailpoet') },
    { key: 'status', label: __('Status', 'mailpoet') },
    { key: 'edit', label: __('Edit', 'mailpoet') },
    { key: 'more', label: __('More', 'mailpoet') },
  ];

  return (
    <TableCard
      title=""
      isLoading={workflows.length === 0 || loading}
      rows={rows}
      headers={headers}
      query={{ page: 2 }}
      rowsPerPage={7}
      totalRows={workflows.length}
      hasSearch
      actions={[
        <ul key="types" className="subsubsub" style={{ width: '400px' }}>
          <li>
            <a href="/">All</a> |
          </li>
          <li>
            <a href="/">Activated</a> |
          </li>
          <li>
            <a href="/">Drafts</a>
          </li>
        </ul>,
        <Search
          allowFreeTextSearch
          inlineTags
          key="search"
          // onChange={ onSearchChange }
          // placeholder={
          //  labels.placeholder ||
          //  __( 'Search by item name', 'woocommerce' )
          // }
          // selected={ searchedLabels }
          showClearButton
          type="custom"
          disabled={loading || workflows.length === 0}
          autocompleter={{}}
        />,
      ]}
    />
  );
}
