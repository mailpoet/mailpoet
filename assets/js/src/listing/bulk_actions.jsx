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
        if(action !== null && action['onSelect'] !== undefined) {
          this.setState({
            extra: action.onSelect(e),
          });
        }
      });
    },
    handleApplyAction: function (e) {
      e.preventDefault();

      const action = this.getSelectedAction();

      if(action === null) {
        return;
      }

      const selected_ids = (this.props.selection !== 'all')
        ? this.props.selected_ids
        : [];

      const data = (action['getData'] !== undefined)
        ? action.getData()
        : {};

      data.action = this.state.action;

      let onSuccess = function () {};
      if(action['onSuccess'] !== undefined) {
        onSuccess = action.onSuccess;
      }

      if(data.action) {
        const promise = this.props.onBulkAction(selected_ids, data);
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
      const selected_action = this.refs.action.value;
      if(selected_action.length > 0) {
        const action = this.props.bulk_actions.filter((action) => {
          return (action.name === selected_action);
        });

        if(action.length > 0) {
          return action[0];
        }
      }
      return null;
    },
    render: function () {
      if(this.props.bulk_actions.length === 0) {
        return null;
      }

      return (
        <div className="alignleft actions bulkactions">
          <label
            className="screen-reader-text"
            htmlFor="bulk-action-selector-top">
            {MailPoet.I18n.t('selectBulkAction')}
          </label>

          <select
            name="bulk_actions"
            ref="action"
            value={ this.state.action }
            onChange={this.handleChangeAction}
          >
            <option value="">{MailPoet.I18n.t('bulkActions')}</option>
            { this.props.bulk_actions.map((action, index) => {
              return (
                <option
                  value={ action.name }
                  key={ 'action-' + index }
                >{ action.label }</option>
              );
            }) }
          </select>
          <input
            onClick={ this.handleApplyAction }
            type="submit"
            defaultValue={MailPoet.I18n.t('apply')}
            className="button action" />

          { this.state.extra }
        </div>
      );
    },
  });

  return ListingBulkActions;
});
