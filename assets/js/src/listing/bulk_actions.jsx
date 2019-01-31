import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

class ListingBulkActions extends React.Component {
  constructor(props) {
    super(props);
    this.myRef = React.createRef();
    this.state = {
      action: false,
      extra: false,
    };
    this.handleApplyAction = this.handleApplyAction.bind(this);
    this.handleChangeAction = this.handleChangeAction.bind(this);
  }

  getSelectedAction() {
    const selectedAction = this.myRef.current.value;
    if (selectedAction.length > 0) {
      const action = this.props.bulk_actions.filter(act => (act.name === selectedAction));

      if (action.length > 0) {
        return action[0];
      }
    }
    return null;
  }

  handleApplyAction(e) {
    e.preventDefault();

    const action = this.getSelectedAction();

    if (action === null) {
      return;
    }

    const selectedIds = (this.props.selection !== 'all')
      ? this.props.selected_ids
      : [];

    const data = (action.getData !== undefined)
      ? action.getData()
      : {};

    data.action = this.state.action;

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
      action: false,
      extra: false,
    });
  }

  handleChangeAction(e) {
    this.setState({
      action: e.target.value,
      extra: false,
    }, () => {
      const action = this.getSelectedAction();

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
      <div className="alignleft actions bulkactions">
        <label
          className="screen-reader-text"
          htmlFor="bulk_actions"
        >
          {MailPoet.I18n.t('selectBulkAction')}
        </label>

        <select
          name="bulk_actions"
          ref={this.myRef}
          value={this.state.action}
          onChange={this.handleChangeAction}
        >
          <option value="">{MailPoet.I18n.t('bulkActions')}</option>
          { this.props.bulk_actions.map(action => (
            <option
              value={action.name}
              key={`action-${action.name}`}
            >
              { action.label }
            </option>
          )) }
        </select>
        <input
          onClick={this.handleApplyAction}
          type="submit"
          value={MailPoet.I18n.t('apply')}
          className="button action"
        />

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
