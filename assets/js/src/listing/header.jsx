define([
    'react',
    'classnames',
    'mailpoet'
  ], function(
    React,
    classNames,
    MailPoet
  ) {

  var ListingHeader = React.createClass({
    handleSelectItems: function() {
      return this.props.onSelectItems(
        this.refs.toggle.checked
      );
    },
    render: function() {
      var columns = this.props.columns.map(function(column, index) {
        column.is_primary = (index === 0);
        column.sorted = (this.props.sort_by === column.name)
          ? this.props.sort_order
          : 'desc';
        return (
          <ListingColumn
            onSort={this.props.onSort}
            sort_by={this.props.sort_by}
            key={ 'column-' + index }
            column={column} />
        );
      }.bind(this));

      var checkbox = false;

      if(this.props.is_selectable === true) {
        checkbox = (
          <th
            className="manage-column column-cb check-column">
            <label className="screen-reader-text">
              {MailPoet.I18n.t('selectAll')}
            </label>
            <input
              type="checkbox"
              name="select_all"
              ref="toggle"
              checked={ this.props.selection }
              onChange={ this.handleSelectItems } />
          </th>
        );
      }

      return (
        <tr>
          {checkbox}
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
        { 'column-primary': this.props.column.is_primary },
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
          scope="col"
          width={ this.props.column.width || null }
        >{label}</th>
      );
    }
  });

  return ListingHeader;
});
