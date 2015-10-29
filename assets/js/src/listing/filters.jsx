define([
  'react',
  'jquery'
],
function(
  React,
  jQuery
) {
  var ListingFilters = React.createClass({
    handleFilterAction: function() {
      let filters = {}
      this.getAvailableFilters().map((filter, i) => {
        filters[this.refs['filter-'+i].name] = this.refs['filter-'+i].value
      })
      return this.props.onSelectFilter(filters);
    },
    getAvailableFilters: function() {
      let filters = this.props.filters;

      return Object.keys(filters).filter(function(filter) {
        return !(
          filters[filter].length === 0
          || (
            filters[filter].length === 1
            && !filters[filter][0].value
          )
        );
      })
    },
    render: function() {
      const filters = this.props.filters;
      const selected_filters = this.props.filter;

      const available_filters = this.getAvailableFilters()
        .map(function(filter, i) {
          let default_value = false;
          if(selected_filters[filter] !== undefined && selected_filters[filter]) {
            default_value = selected_filters[filter]

          } else {
            jQuery(`select[name="${filter}"]`).val('');
          }
          return (
            <select
              ref={ `filter-${i}` }
              key={ `filter-${i}` }
              name={ filter }
              defaultValue={ default_value }
            >
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

      let button = false;

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
