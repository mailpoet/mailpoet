import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import ListingItem from 'listing/listing_item.jsx';

class ListingItems extends React.Component { // eslint-disable-line react/prefer-stateless-function, max-len
  render() {
    if (this.props.items.length === 0) {
      let message;
      if (this.props.loading === true) {
        message = (this.props.messages.onLoadingItems
          && this.props.messages.onLoadingItems(this.props.group))
          || MailPoet.I18n.t('loadingItems');
      } else {
        message = (this.props.messages.onNoItemsFound
          && this.props.messages.onNoItemsFound(this.props.group, this.props.search))
          || MailPoet.I18n.t('noItemsFound');
      }

      return (
        <tbody>
          <tr className="mailpoet-listing-no-items">
            <td
              colSpan={
                this.props.columns.length
                + (this.props.is_selectable ? 1 : 0)
              }
              className="colspanchange"
            >
              {message}
            </td>
          </tr>
        </tbody>
      );
    }
    const selectAllClasses = classNames(
      'mailpoet-listing-select-all',
      {
        mailpoet_hidden: (
          this.props.selection === false
            || (this.props.count <= this.props.limit)
        ),
      }
    );

    return (
      <tbody>
        <tr className={selectAllClasses}>
          <td colSpan={
            this.props.columns.length
                + (this.props.is_selectable ? 1 : 0)
          }
          >
            {
              (this.props.selection !== 'all')
                ? MailPoet.I18n.t('selectAllLabel')
                : MailPoet.I18n.t('selectedAllLabel').replace(
                  '%d',
                  this.props.count.toLocaleString()
                )
            }
              &nbsp;
            <a
              href="#"
              onClick={(event) => {
                event.preventDefault();
                this.props.onSelectAll(event);
              }}
            >
              {
                (this.props.selection !== 'all')
                  ? MailPoet.I18n.t('selectAllLink')
                  : MailPoet.I18n.t('clearSelection')
              }
            </a>
          </td>
        </tr>

        {this.props.items.map((item) => {
          const renderItem = item;
          renderItem.id = parseInt(item.id, 10);
          renderItem.selected = (this.props.selected_ids.indexOf(renderItem.id) !== -1);
          let key = `item-${renderItem.id}-${item.id}`;
          if (typeof this.props.getListingItemKey === 'function') {
            key = this.props.getListingItemKey(item);
          }

          return (
            <ListingItem
              columns={this.props.columns}
              onSelectItem={this.props.onSelectItem}
              onRenderItem={this.props.onRenderItem}
              onDeleteItem={this.props.onDeleteItem}
              onRestoreItem={this.props.onRestoreItem}
              onTrashItem={this.props.onTrashItem}
              onRefreshItems={this.props.onRefreshItems}
              selection={this.props.selection}
              is_selectable={this.props.is_selectable}
              item_actions={this.props.item_actions}
              group={this.props.group}
              key={key}
              item={renderItem}
            />
          );
        })}
      </tbody>
    );
  }
}

ListingItems.propTypes = {
  items: PropTypes.arrayOf(PropTypes.object).isRequired,
  loading: PropTypes.bool.isRequired,
  messages: PropTypes.shape({
    onLoadingItems: PropTypes.func,
    onNoItemsFound: PropTypes.func,
  }).isRequired,
  group: PropTypes.string.isRequired,
  columns: PropTypes.arrayOf(PropTypes.object).isRequired,
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
  item_actions: PropTypes.arrayOf(PropTypes.object).isRequired,
  search: PropTypes.string,
};

ListingItems.defaultProps = {
  getListingItemKey: undefined,
  search: undefined,
};

export default ListingItems;
