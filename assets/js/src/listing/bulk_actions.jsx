define([
  'react',
  'mailpoet',
],
(
  React,
  MailPoet
) => {
  const ListingBulkActions = React.createClass({
    getInitialState: function () {
      return {
        action: false,
        extra: false,
      };
    },
    handleChangeAction: function (e) {
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
    },
    handleApplyAction: function (e) {
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

      let onSuccess = function () {};
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
    },
    getSelectedAction: function () {
      const selectedAction = this.action.value;
      if (selectedAction.length > 0) {
        const action = this.props.bulk_actions.filter(act => (act.name === selectedAction));

        if (action.length > 0) {
          return action[0];
        }
      }
      return null;
    },
    render: function () {
      if (this.props.bulk_actions.length === 0) {
        return null;
      }

      return (
        <div className="alignleft actions bulkactions">
          <label
            className="screen-reader-text"
            htmlFor="bulk-action-selector-top"
          >
            {MailPoet.I18n.t('selectBulkAction')}
          </label>

          <select
            name="bulk_actions"
            ref={(c) => { this.action = c; }}
            value={this.state.action}
            onChange={this.handleChangeAction}
          >
            <option value="">{MailPoet.I18n.t('bulkActions')}</option>
            { this.props.bulk_actions.map((action, index) => (
              <option
                value={action.name}
                key={`action-${index}`}
              >{ action.label }</option>
              )) }
          </select>
          <input
            onClick={this.handleApplyAction}
            type="submit"
            defaultValue={MailPoet.I18n.t('apply')}
            className="button action"
          />

          { this.state.extra }
        </div>
      );
    },
  });

  return ListingBulkActions;
});
