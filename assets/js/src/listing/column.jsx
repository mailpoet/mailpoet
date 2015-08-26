define('listing.column', ['react', 'classnames'], function(React, classNames) {
  /*
    props:
      onSort -> callback(sort_by, sort_order)
      column -> {
        sorted: (string) asc | desc
        sortable: (bool)
        name: (string) field name
        label: (string) displayed label
  */
  var ListingColumn = React.createClass({
    handleSort: function() {
      var sort_by = this.props.column.name,
          sort_order = (this.props.column.sorted === 'asc') ? 'desc' : 'asc';
      this.props.onSort(sort_by, sort_order);
    },
    render: function() {
      var classes = classNames(
        'manage-column',
        { 'sortable': this.props.column.sortable },
        this.props.column.sorted
      );

      var label;

      if(this.props.column.sortable === true) {
        label = (
          <a onClick={this.handleSort}>
            <span>{ this.props.column.label }</span>
            <span className="sorting-indicator"></span>
          </a>
        );
      } else {
        label = this.props.column.label;
      }
      return (
        <th
          className={ classes }
          id={this.props.column.name }
          scope="col">
          {label}
        </th>
      );
    }
  });

  return ListingColumn;
});