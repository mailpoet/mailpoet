import MailPoet from 'mailpoet';
import React from 'react';
import classNames from 'classnames';

const ListingHeader = React.createClass({
  handleSelectItems: function () {
    return this.props.onSelectItems(
      this.refs.toggle.checked
    );
  },
  render: function () {
    const columns = this.props.columns.map((column, index) => {
      const renderColumn = column;
      renderColumn.is_primary = (index === 0);
      renderColumn.sorted = (this.props.sort_by === column.name)
        ? this.props.sort_order
        : 'desc';
      return (
        <ListingColumn
          onSort={this.props.onSort}
          sort_by={this.props.sort_by}
          key={`column-${index}`}
          column={renderColumn} />
      );
    });

    let checkbox;

    if (this.props.is_selectable === true) {
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
            checked={this.props.selection}
            onChange={this.handleSelectItems} />
        </th>
      );
    }

    return (
      <tr>
        {checkbox}
        {columns}
      </tr>
    );
  },
});

const ListingColumn = React.createClass({
  handleSort: function () {
    const sortBy = this.props.column.name;
    const sortOrder = (this.props.column.sorted === 'asc') ? 'desc' : 'asc';
    this.props.onSort(sortBy, sortOrder);
  },
  render: function () {
    const classes = classNames(
      'manage-column',
      { 'column-primary': this.props.column.is_primary },
      { sortable: this.props.column.sortable },
      this.props.column.sorted,
      { sorted: (this.props.sort_by === this.props.column.name) }
    );
    let label;

    if (this.props.column.sortable === true) {
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
        className={classes}
        id={this.props.column.name}
        scope="col"
        width={this.props.column.width || null}
      >{label}</th>
    );
  },
});

module.exports = ListingHeader;
