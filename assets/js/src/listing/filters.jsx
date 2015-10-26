define([
  'react'
],
function(
  React
) {
  var ListingFilters = React.createClass({
    handleFilterAction: function() {
      var filters = this.props.filters;
      var selected_filters = Object.keys(filters)
        .map(function(filter, index) {
          var value = this.refs.filter.value;
          if(value) {
            var output = {};
            output[filter] = value;
            return output;
          }
        }.bind(this)
      );
      return this.props.onSelectFilter(selected_filters);
    },
    handleChangeAction: function() {
      return this.refs.filter.value;
    },
    render: function() {
      var filters = this.props.filters;
      var selected_filters = this.props.filter;

      var available_filters = Object.keys(filters)
        .filter(function(filter) {
          return !(
            filters[filter].length === 0
            || (
              filters[filter].length === 1
              && !filters[filter][0].value
            )
          );
        })
        .map(function(filter, i) {
          var defaultValue = false;
          if(selected_filters[filter] !== undefined) {
            defaultValue = selected_filters[filter];
          }
          return (
            <select
              ref={ 'filter' }
              key={ 'filter-'+i }
              defaultValue={ defaultValue }
              onChange={ this.handleChangeAction }>
              { filters[filter].map(function(option, j) {
                return (
                  <option
                    value={ option.value }
                    key={ 'filter-option-' + j }
                  >{ option.label }</option>
                );
              }.bind(this)) }
            </select>
          );
      }.bind(this));

      var button = false;

      if(available_filters.length > 0) {
        button = (
          <input
            onClick={ this.handleFilterAction }
            type="submit"
            defaultValue="Filter"
            className="button" />
        );
      }

      return (
        <div className="alignleft actions actions">
          { available_filters }
          { button }
        </div>
      );
    }
  });

  return ListingFilters;
});
