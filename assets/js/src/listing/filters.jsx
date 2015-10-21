define([
  'react'
],
function(
  React
) {
  var ListingFilters = React.createClass({
    handleFilterAction: function() {
      var filters = this.props.filters.map(function(filter, index) {
        var value = this.refs['filter-'+index].value;
        if(value) {
          return {
            'name': filter.name,
            'value': value
          };
        }
      }.bind(this));
      return this.props.onSelectFilter(filters);
    },
    handleChangeAction: function() {
      return true;
    },
    render: function() {
      var filters = this.props.filters
        .filter(function(filter) {
          return !(
            filter.options.length === 0
            || (
              filter.options.length === 1
              && !filter.options[0].value
            )
          );
        })
        .map(function(filter, i) {
          return (
            <select
              ref={ 'filter-'+i }
              key={ 'filter-'+i }
              onChange={ this.handleChangeAction }>
              { filter.options.map(function(option, j) {
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

      if(filters.length > 0) {
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
          { filters }
          { button }
        </div>
      );
    }
  });

  return ListingFilters;
});
