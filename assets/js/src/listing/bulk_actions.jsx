import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

class ListingBulkActions extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      extra: false,
    };
    this.handleApplyAction = this.handleApplyAction.bind(this);
    this.handleChangeAction = this.handleChangeAction.bind(this);
  }

  getSelectedAction(actionName) {
    const selectedAction = actionName;
    if (selectedAction.length > 0) {
      const action = this.props.bulk_actions.filter((act) => (act.name === selectedAction));

      if (action.length > 0) {
        return action[0];
      }
    }
    return null;
  }

  handleApplyAction(actionName) {
    const action = this.getSelectedAction(actionName);

    if (action === null) {
      return;
    }

    const selectedIds = (this.props.selection !== 'all')
      ? this.props.selected_ids
      : [];

    const data = (action.getData !== undefined)
      ? action.getData()
      : {};

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

  handleChangeAction(e, actionName) {
    this.setState({
      extra: false,
    }, () => {
      const action = this.getSelectedAction(actionName);

      // action on select callback
      if (action !== null && action.onSelect !== undefined) {
        this.setState({
          extra: action.onSelect(e),
        });
      }
    });
  }

  render() {
    if (this.props.bulk_actions.length === 0) {
      return null;
    }

    return (
      <div className="mailpoet-listing-bulk-actions">
        <span className="screen-reader-text">
          {MailPoet.I18n.t('selectBulkAction')}
        </span>

        <div>
          { this.props.bulk_actions.map((action) => (
            <a
              href="#"
              key={`action-${action.name}`}
              onClick={(e) => {
                /* this.handleChangeAction(e, action.name); */
                e.preventDefault();
                return this.handleApplyAction(action.name);
              }}
            >
              { action.label }
            </a>
          )) }
        </div>

        { this.state.extra }
      </div>
    );
  }
}

ListingBulkActions.propTypes = {
  bulk_actions: PropTypes.arrayOf(PropTypes.object).isRequired,
  selection: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.bool,
  ]).isRequired,
  selected_ids: PropTypes.arrayOf(PropTypes.number).isRequired,
  onBulkAction: PropTypes.func.isRequired,
};

export default ListingBulkActions;
