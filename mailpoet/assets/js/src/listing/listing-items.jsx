import { Component } from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

import { Grid } from 'common/grid';
import { ListingBulkActions } from 'listing/bulk-actions.jsx';
import { ListingItem } from 'listing/listing-item.jsx';

// eslint-disable-next-line react/prefer-stateless-function, max-len
class ListingItems extends Component {
  render() {
    const {
      bulk_actions: bulkActions,
      count,
      columns,
      group,
      items,
      is_selectable: isSelectable,
      isItemInactive,
      item_actions: itemActions,
      limit,
      loading,
      messages,
      onBulkAction,
      onDeleteItem,
      onRefreshItems,
      onRenderItem,
      onRestoreItem,
      onSelectAll,
      onSelectItem,
      onTrashItem,
      selected_ids: selectedIds,
      selection,
      getListingItemKey = undefined,
      search = undefined,
      location = undefined,
      isItemDeletable = () => true,
      isItemToggleable = () => false,
    } = this.props;
    if (items.length === 0) {
      let message;
      if (loading === true) {
        message =
          (messages.onLoadingItems && messages.onLoadingItems(group)) ||
          __('Loading ...', 'mailpoet');
      } else {
        message =
          (messages.onNoItemsFound && messages.onNoItemsFound(group, search)) ||
          __('No items found.', 'mailpoet');
      }

      return (
        <tbody>
          <tr className="mailpoet-listing-no-items">
            <td
              colSpan={columns.length + (isSelectable ? 1 : 0)}
              className="colspanchange"
            >
              {message}
            </td>
          </tr>
        </tbody>
      );
    }

    const isSelectAllHidden = selection === false || count <= limit;
    const areBulkActionsHidden = !(selectedIds.length > 0 || selection);

    const actionAndSelectAllRowClasses = classnames(
      'mailpoet-listing-actions-and-select-all-row',
      {
        mailpoet_hidden: areBulkActionsHidden && isSelectAllHidden,
      },
    );
    const selectAllClasses = classnames('mailpoet-listing-select-all', {
      mailpoet_hidden: isSelectAllHidden,
    });

    return (
      <tbody>
        <tr className={actionAndSelectAllRowClasses}>
          <td colSpan={columns.length + (isSelectable ? 1 : 0)}>
            <Grid.SpaceBetween verticalAlign="center">
              <div className="mailpoet-listing-bulk-actions-container">
                {!areBulkActionsHidden && (
                  <ListingBulkActions
                    count={count}
                    bulk_actions={bulkActions}
                    selection={selection}
                    selected_ids={selectedIds}
                    onBulkAction={onBulkAction}
                  />
                )}
              </div>
              <div className={selectAllClasses}>
                {selection !== 'all'
                  ? __('All items on this page are selected.', 'mailpoet')
                  : __('All %d items are selected.', 'mailpoet').replace(
                      '%d',
                      count.toLocaleString(),
                    )}
                &nbsp;
                <a
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    onSelectAll(event);
                  }}
                >
                  {selection !== 'all'
                    ? __('Select all items on all pages', 'mailpoet')
                    : __('Clear selection', 'mailpoet')}
                </a>
                .
              </div>
            </Grid.SpaceBetween>
          </td>
        </tr>

        {items.map((item) => {
          const renderItem = item;
          renderItem.id = parseInt(item.id, 10);
          renderItem.selected = selectedIds.indexOf(renderItem.id) !== -1;
          let key = `item-${renderItem.id}-${item.id}`;
          if (typeof getListingItemKey === 'function') {
            key = getListingItemKey(item);
          }

          return (
            <ListingItem
              columns={columns}
              isItemInactive={isItemInactive}
              onSelectItem={onSelectItem}
              onRenderItem={onRenderItem}
              onDeleteItem={onDeleteItem}
              onRestoreItem={onRestoreItem}
              onTrashItem={onTrashItem}
              onRefreshItems={onRefreshItems}
              selection={selection}
              is_selectable={isSelectable}
              item_actions={itemActions}
              group={group}
              location={location}
              key={key}
              item={renderItem}
              isItemDeletable={isItemDeletable}
              isItemToggleable={isItemToggleable}
            />
          );
        })}
      </tbody>
    );
  }
}

ListingItems.propTypes = {
  items: PropTypes.arrayOf(PropTypes.object).isRequired, // eslint-disable-line react/forbid-prop-types
  loading: PropTypes.bool.isRequired,
  messages: PropTypes.shape({
    onLoadingItems: PropTypes.func,
    onNoItemsFound: PropTypes.func,
  }).isRequired,
  group: PropTypes.string.isRequired,
  columns: PropTypes.arrayOf(PropTypes.object).isRequired, // eslint-disable-line react/forbid-prop-types
  is_selectable: PropTypes.bool.isRequired,
  selection: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.number,
    PropTypes.bool,
  ]).isRequired,
  count: PropTypes.number.isRequired,
  limit: PropTypes.number.isRequired,
  onSelectAll: PropTypes.func.isRequired,
  selected_ids: PropTypes.arrayOf(PropTypes.number).isRequired,
  getListingItemKey: PropTypes.func,
  onSelectItem: PropTypes.func.isRequired,
  onRenderItem: PropTypes.func.isRequired,
  onDeleteItem: PropTypes.func.isRequired,
  onRestoreItem: PropTypes.func.isRequired,
  onTrashItem: PropTypes.func.isRequired,
  onRefreshItems: PropTypes.func.isRequired,
  isItemInactive: PropTypes.func.isRequired,
  item_actions: PropTypes.arrayOf(PropTypes.object).isRequired, // eslint-disable-line react/forbid-prop-types
  bulk_actions: PropTypes.arrayOf(PropTypes.object).isRequired, // eslint-disable-line react/forbid-prop-types
  onBulkAction: PropTypes.func.isRequired,
  search: PropTypes.string,
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }),
  isItemDeletable: PropTypes.func,
  isItemToggleable: PropTypes.func,
};

export { ListingItems };
