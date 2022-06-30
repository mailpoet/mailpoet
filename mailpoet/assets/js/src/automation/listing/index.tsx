import { Search, TableCard } from '@woocommerce/components';
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getRow } from './get-row';
import { Workflow } from './workflow';

type Props = {
  workflows: Workflow[];
  loading: boolean;
};

export function AutomationListing({ workflows, loading }: Props): JSX.Element {
  const rows = workflows.map((workflow) => getRow(workflow));

  const headers = [
    { key: 'name', label: __('Name', 'mailpoet') },
    { key: 'subscribers', label: __('Subscribers', 'mailpoet') },
    { key: 'status', label: __('Status', 'mailpoet') },
    { key: 'edit' },
    { key: 'more' },
  ];

  return (
    <TableCard
      className="mailpoet-automation-listing"
      title=""
      isLoading={workflows.length === 0 || loading}
      headers={headers}
      rows={rows}
      rowKey={(data, i) => data[i].id}
      query={{ page: 2 }}
      rowsPerPage={7}
      totalRows={workflows.length}
      hasSearch
      showMenu={false}
      actions={[
        <div key="actions" className="mailpoet-automation-listing-actions">
          <Dropdown
            renderToggle={({ isOpen, onToggle }) => (
              <Button
                className="mailpoet-automation-listing-action-button"
                variant="secondary"
                aria-expanded={isOpen}
                onClick={onToggle}
                label=""
                disabled
              >
                Actions
              </Button>
            )}
            renderContent={() => (
              <MenuGroup>
                <MenuItem icon="trash">Move to trash</MenuItem>
              </MenuGroup>
            )}
          />
        </div>,
        <Search
          className="mailpoet-automation-listing-search"
          allowFreeTextSearch
          inlineTags
          key="search"
          // onChange={ onSearchChange }
          // placeholder={
          //  labels.placeholder ||
          //  __( 'Search by item name', 'woocommerce' )
          // }
          // selected={ searchedLabels }
          type="custom"
          disabled={loading || workflows.length === 0}
          autocompleter={{}}
        />,
      ]}
    />
  );
}
