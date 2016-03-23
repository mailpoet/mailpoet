define([
  'react'
],
function(
  React
) {
  var ListingBulkActions = React.createClass({
    getInitialState: function() {
      return {
        action: false,
        extra: false
      }
    },
    handleChangeAction: function(e) {
      this.setState({
        action: e.target.value,
        extra: false
      }, function() {
        var action = this.getSelectedAction();

        // action on select callback
        if(action !== null && action['onSelect'] !== undefined) {
          this.setState({
            extra: action.onSelect(e)
          });
        }
      }.bind(this));
    },
    handleApplyAction: function(e) {
      e.preventDefault();

      var action = this.getSelectedAction();

      if(action === null) {
        return;
      }

      var selected_ids = (this.props.selection !== 'all')
        ? this.props.selected_ids
        : [];

      var data = (action['getData'] !== undefined)
        ? action.getData()
        : {};

      data.action = this.state.action;

      var callback = function() {};
      if(action['onSuccess'] !== undefined) {
        callback = action.onSuccess;
      }

      if(data.action) {
        this.props.onBulkAction(selected_ids, data, callback);
      }

      this.setState({
        action: false,
        extra: false
      });
    },
    getSelectedAction: function() {
      var selected_action = this.refs.action.value;
      if(selected_action.length > 0) {
        var action = this.props.bulk_actions.filter(function(action) {
          return (action.name === selected_action);
        });

        if(action.length > 0) {
          return action[0];
        }
      }
      return null;
    },
    render: function() {
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
            { this.props.bulk_actions.map(function(action, index) {
              return (
                <option
                  value={ action.name }
                  key={ 'action-' + index }
                >{ action.label }</option>
              );
            }.bind(this)) }
          </select>
          <input
            onClick={ this.handleApplyAction }
            type="submit"
            defaultValue="Apply"
            className="button action" />

            { this.state.extra }
        </div>
      );
    }
  });

  return ListingBulkActions;
});
