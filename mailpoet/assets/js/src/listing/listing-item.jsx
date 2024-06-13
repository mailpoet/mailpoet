import { Component } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import classnames from 'classnames';
import { Checkbox } from 'common/form/checkbox/checkbox.tsx';

class ListingItem extends Component {
  handleSelectItem = (e) => {
    this.props.onSelectItem(parseInt(e.target.value, 10), e.target.checked);

    return !e.target.checked;
  };

  handleRestoreItem = (id) => {
    this.props.onRestoreItem(id);
  };

  handleTrashItem = (id) => {
    this.props.onTrashItem(id);
  };

  handleDeleteItem = (id) => {
    this.props.onDeleteItem(id);
  };

  render() {
    let checkbox = false;

    const {
      is_selectable: isSelectable,
      item,
      columns,
      group,
      selection,
      item_actions: propsItemActions,
      onRefreshItems,
      isItemInactive,
      onRenderItem,
      location = undefined,
      isItemDeletable = () => true,
      isItemToggleable = () => false,
    } = this.props;

    if (isSelectable === true) {
      checkbox = (
        <th
          className="mailpoet-listing-check-column mailpoet-hide-on-mobile"
          scope="row"
        >
          <label
            className="screen-reader-text"
            htmlFor={`listing-row-checkbox-${item.id}`}
          >
            {`Select ${item[columns[0].name]}`}
          </label>
          <Checkbox
            value={item.id}
            checked={item.selected || selection === 'all'}
            onCheck={() => {}}
            onChange={this.handleSelectItem}
            disabled={selection === 'all'}
            id={`listing-row-checkbox-${item.id}`}
            automationId={`listing-row-checkbox-${item.id}`}
          />
        </th>
      );
    }

    const customActions = propsItemActions;
    let itemActions = false;

    if (customActions.length > 0) {
      itemActions = customActions
        .filter(
          (action) =>
            action.display === undefined || action.display(this.props.item),
        )
        .map((action, index) => {
          let customAction = null;

          if (action.name === 'trash') {
            customAction = (
              <span
                key={`action-${action.name}`}
                className={classnames(action.name, action.className)}
              >
                <a
                  type="button"
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    this.handleTrashItem(item.id);
                  }}
                >
                  {isItemToggleable(item)
                    ? __('Trash and disable', 'mailpoet')
                    : __('Move to trash', 'mailpoet')}
                </a>
              </span>
            );
          } else if (action.refresh) {
            customAction = (
              <span
                onClick={onRefreshItems}
                key={`action-${action.name}`}
                className={classnames(action.name, action.className)}
                role="button"
                tabIndex={index}
                onKeyDown={(event) => {
                  if (
                    ['keydown', 'keypress'].includes(event.type) &&
                    ['Enter', ' '].includes(event.key)
                  ) {
                    event.preventDefault();
                    onRefreshItems();
                  }
                }}
              >
                {action.link(item)}
              </span>
            );
          } else if (action.link) {
            customAction = (
              <span
                key={`action-${action.name}`}
                className={classnames(action.name, action.className)}
              >
                {action.link(item, location)}
              </span>
            );
          } else {
            customAction = (
              <span
                key={`action-${action.name}`}
                className={classnames(action.name, action.className)}
              >
                <a
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    if (action.onClick !== undefined) {
                      action.onClick(item, onRefreshItems);
                    }
                  }}
                >
                  {action.label}
                </a>
              </span>
            );
          }
          return customAction;
        });
    } else {
      itemActions = (
        <span className="edit mailpoet-hide-on-mobile">
          <Link
            to={{
              pathname: `/edit/${item.id}`,
              state: {
                backUrl: location?.pathname,
              },
            }}
          >
            {__('Edit', 'mailpoet')}
          </Link>
        </span>
      );
    }

    let actions;

    if (group === 'trash') {
      actions = (
        <div className="mailpoet-listing-actions-holder">
          <div className="mailpoet-listing-actions">
            <span>
              <a
                href="#"
                onClick={(event) => {
                  event.preventDefault();
                  this.handleRestoreItem(item.id);
                }}
              >
                {isItemToggleable(item)
                  ? __('Restore and enable', 'mailpoet')
                  : __('Restore', 'mailpoet')}
              </a>
            </span>
            {isItemDeletable(item) && (
              <span className="delete">
                <a
                  className="submitdelete"
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    this.handleDeleteItem(item.id);
                  }}
                >
                  {__('Delete permanently', 'mailpoet')}
                </a>
              </span>
            )}
          </div>
        </div>
      );
    } else {
      actions = (
        <div className="mailpoet-listing-actions-holder">
          <div className="mailpoet-listing-actions">{itemActions}</div>
        </div>
      );
    }

    const rowClasses = classnames({
      'mailpoet-listing-row-selected': item.selected || selection === 'all',
      'mailpoet-listing-row-inactive': isItemInactive(item),
    });

    return (
      <tr className={rowClasses} data-automation-id={`listing_item_${item.id}`}>
        {checkbox}
        {onRenderItem(item, actions)}
      </tr>
    );
  }
}

ListingItem.propTypes = {
  onSelectItem: PropTypes.func.isRequired,
  onRestoreItem: PropTypes.func.isRequired,
  onTrashItem: PropTypes.func.isRequired,
  onDeleteItem: PropTypes.func.isRequired,
  is_selectable: PropTypes.bool.isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  columns: PropTypes.arrayOf(PropTypes.object).isRequired, // eslint-disable-line react/forbid-prop-types
  selection: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.number,
    PropTypes.bool,
  ]).isRequired,
  item_actions: PropTypes.arrayOf(PropTypes.object).isRequired, // eslint-disable-line react/forbid-prop-types
  onRefreshItems: PropTypes.func.isRequired,
  onRenderItem: PropTypes.func.isRequired,
  isItemInactive: PropTypes.func.isRequired,
  group: PropTypes.string.isRequired,
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }),
  isItemDeletable: PropTypes.func,
  isItemToggleable: PropTypes.func,
};

export { ListingItem };
