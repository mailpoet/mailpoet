import { Component } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import Checkbox from 'common/form/checkbox/checkbox.tsx';

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

    if (this.props.is_selectable === true) {
      checkbox = (
        <th
          className="mailpoet-listing-check-column mailpoet-hide-on-mobile"
          scope="row"
        >
          <label
            className="screen-reader-text"
            htmlFor={`listing-row-checkbox-${this.props.item.id}`}
          >
            {`Select ${this.props.item[this.props.columns[0].name]}`}
          </label>
          <Checkbox
            value={this.props.item.id}
            checked={this.props.item.selected || this.props.selection === 'all'}
            onCheck={() => {}}
            onChange={this.handleSelectItem}
            disabled={this.props.selection === 'all'}
            id={`listing-row-checkbox-${this.props.item.id}`}
            automationId={`listing-row-checkbox-${this.props.item.id}`}
          />
        </th>
      );
    }

    const customActions = this.props.item_actions;
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
                className={classNames(action.name, action.className)}
              >
                <a
                  type="button"
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    this.handleTrashItem(this.props.item.id);
                  }}
                >
                  {this.props.isItemToggleable(this.props.item)
                    ? MailPoet.I18n.t('trashAndDisable')
                    : MailPoet.I18n.t('moveToTrash')}
                </a>
              </span>
            );
          } else if (action.refresh) {
            customAction = (
              <span
                onClick={this.props.onRefreshItems}
                key={`action-${action.name}`}
                className={classNames(action.name, action.className)}
                role="button"
                tabIndex={index}
                onKeyDown={(event) => {
                  if (
                    ['keydown', 'keypress'].includes(event.type) &&
                    ['Enter', ' '].includes(event.key)
                  ) {
                    event.preventDefault();
                    this.props.onRefreshItems();
                  }
                }}
              >
                {action.link(this.props.item)}
              </span>
            );
          } else if (action.link) {
            customAction = (
              <span
                key={`action-${action.name}`}
                className={classNames(action.name, action.className)}
              >
                {action.link(this.props.item, this.props.location)}
              </span>
            );
          } else {
            customAction = (
              <span
                key={`action-${action.name}`}
                className={classNames(action.name, action.className)}
              >
                <a
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    if (action.onClick !== undefined) {
                      action.onClick(
                        this.props.item,
                        this.props.onRefreshItems,
                      );
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
              pathname: `/edit/${this.props.item.id}`,
              state: {
                backUrl: this.props.location?.pathname,
              },
            }}
          >
            {MailPoet.I18n.t('edit')}
          </Link>
        </span>
      );
    }

    let actions;

    if (this.props.group === 'trash') {
      actions = (
        <div className="mailpoet-listing-actions-holder">
          <div className="mailpoet-listing-actions">
            <span>
              <a
                href="#"
                onClick={(event) => {
                  event.preventDefault();
                  this.handleRestoreItem(this.props.item.id);
                }}
              >
                {this.props.isItemToggleable(this.props.item)
                  ? MailPoet.I18n.t('restoreAndEnable')
                  : MailPoet.I18n.t('restore')}
              </a>
            </span>
            {this.props.isItemDeletable(this.props.item) && (
              <span className="delete">
                <a
                  className="submitdelete"
                  href="#"
                  onClick={(event) => {
                    event.preventDefault();
                    this.handleDeleteItem(this.props.item.id);
                  }}
                >
                  {MailPoet.I18n.t('deletePermanently')}
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

    const rowClasses = classNames({
      'mailpoet-listing-row-selected':
        this.props.item.selected || this.props.selection === 'all',
      'mailpoet-listing-row-inactive': this.props.isItemInactive(
        this.props.item,
      ),
    });

    return (
      <tr
        className={rowClasses}
        data-automation-id={`listing_item_${this.props.item.id}`}
      >
        {checkbox}
        {this.props.onRenderItem(this.props.item, actions)}
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
  columns: PropTypes.arrayOf(PropTypes.object).isRequired,
  selection: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.number,
    PropTypes.bool,
  ]).isRequired,
  item_actions: PropTypes.arrayOf(PropTypes.object).isRequired,
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

ListingItem.defaultProps = {
  location: undefined,
  isItemDeletable: () => true,
  isItemToggleable: () => false,
};

export default ListingItem;
