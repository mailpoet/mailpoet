define(['react'], function(React) {

  var ListingBulkActions = React.createClass({
    handleChangeAction: function(e) {
      var action = this.getSelectedAction();

      if(action !== null && action['onSelect'] !== undefined) {
        action.onSelect(e);
      }
    },
    handleApplyAction: function(e) {
      e.preventDefault();

      var action = this.getSelectedAction();

      if(action !== null && action['onApply'] !== undefined) {
        action.onApply();
      }
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

          <select ref="action" onChange={this.handleChangeAction}>
            <option value="">Bulk Actions</option>
            {this.props.bulk_actions.map(function(action, index) {
              return (
                <option
                  value={action.name}
                  key={index}
                >{ action.label }</option>
              );
            }.bind(this))}
          </select>
          <input
            onClick={this.handleApplyAction}
            type="submit"
            defaultValue="Apply"
            className="button action" />
        </div>
      );
    }
  });

  return ListingBulkActions;
});