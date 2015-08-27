define('listing.header', ['react', 'classnames'], function(React, classNames) {
  /*
    props:
      onSort: callback(sort_by, sort_order)
      onSelectAll: callback(is_checked)
      sort_by: (string) field name
      columns -> (array)
        column -> {
          sorted: (string) asc | desc
          sortable: (bool)
          name: (string) field name
          label: (string) displayed label
        }
  */
  var ListingHeader = React.createClass({
    handleSelectAll: function() {
      return this.props.onSelectAll(
        this.refs.select_all.getDOMNode().checked
      );
    },
    render: function() {
      var columns = this.props.columns.map(function(column) {
            return (
              <ListingColumn
                onSort={this.props.onSort}
                sort_by={this.props.sort_by}
                key={column.name}
                column={column} />
            );
      }.bind(this));

      return (
        <tr>
          <td className="manage-column column-cb check-column" id="cb">
            <label className="screen-reader-text">
              { 'Select All' }
            </label>
            <input
              type="checkbox"
              ref="select_all"
              onChange={this.handleSelectAll} />
          </td>
          {columns}
        </tr>
      );
    }
  });

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
        this.props.column.sorted,
        { 'sorted': (this.props.sort_by === this.props.column.name) }
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

  return ListingHeader;
});