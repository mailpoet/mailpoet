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
      });

      var action = this.getSelectedAction();

      // action on select callback
      if(action !== null && action['onSelect'] !== undefined) {
        this.setState({
          extra: action.onSelect(e)
        });
      }
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

      if(data.action) {
        this.props.onBulkAction(selected_ids, data);
      }

      this.setState({
        action: false,
        extra: false
      });
    },
    getSelectedAction: function() {
      var selected_action = jQuery(this.refs.action.getDOMNode()).val();
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
            Select bulk action
          </label>

          <select ref="action" value={ this.state.action } onChange={this.handleChangeAction}>
            <option value="">Bulk Actions</option>
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