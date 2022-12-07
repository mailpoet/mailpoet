import { Component } from 'react';

import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { withBoundary } from '../common';

class ListingBulkActions extends Component {
  constructor(props) {
    super(props);
    this.state = {
      extra: false,
    };
    this.handleApplyAction = this.handleApplyAction.bind(this);
  }

  handleApplyAction(actionName) {
    const action = this.getSelectedAction(actionName);

    if (action === null) {
      return;
    }

    // action on select callback
    if (action.onSelect !== undefined && !this.state.extra) {
      const submitModal = () => this.handleApplyAction(actionName);
      const closeModal = () => this.setState({ extra: false });
      this.setState({
        extra: action.onSelect(submitModal, closeModal, this.props),
      });
      return;
    }

    const selectedIds =
      this.props.selection !== 'all' ? this.props.selected_ids : [];

    const data = action.getData !== undefined ? action.getData() : {};

    data.action = action.name;

    let onSuccess = () => {};
    if (action.onSuccess !== undefined) {
      onSuccess = action.onSuccess;
    }

    if (data.action) {
      const promise = this.props.onBulkAction(selectedIds, data);
      if (promise !== false) {
        promise.then(onSuccess);
      }
    }

    this.setState({
      extra: false,
    });
  }

  getSelectedAction(actionName) {
    const selectedAction = actionName;
    if (selectedAction.length > 0) {
      const action = this.props.bulk_actions.filter(
        (act) => act.name === selectedAction,
      );

      if (action.length > 0) {
        return action[0];
      }
    }
    return null;
  }

  render() {
    if (this.props.bulk_actions.length === 0) {
      return null;
    }

    return (
      <div
        className="mailpoet-listing-bulk-actions"
        data-automation-id="listing-bulk-actions"
      >
        <span className="screen-reader-text">
          {MailPoet.I18n.t('selectBulkAction')}
        </span>

        <div>
          {this.props.bulk_actions.map((action) => (
            <a
              href="#"
              data-automation-id={`action-${action.name}`}
              key={`action-${action.name}`}
              onClick={(e) => {
                e.preventDefault();
                return this.handleApplyAction(action.name);
              }}
            >
              {action.label}
            </a>
          ))}
        </div>

        {this.state.extra}
      </div>
    );
  }
}

ListingBulkActions.propTypes = {
  bulk_actions: PropTypes.arrayOf(PropTypes.object).isRequired,
  selection: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]).isRequired,
  selected_ids: PropTypes.arrayOf(PropTypes.number).isRequired,
  onBulkAction: PropTypes.func.isRequired,
};
ListingBulkActions.displayName = 'ListingBulkActions';
const ListingBulkActionsWithBoundary = withBoundary(ListingBulkActions);
export { ListingBulkActionsWithBoundary as ListingBulkActions };
