import React from 'react';
import classNames from 'classnames';
import PropTypes from 'prop-types';

class ListingColumn extends React.Component {
  handleSort = () => {
    const sortBy = this.props.column.name;
    const sortOrder = (this.props.column.sorted === 'asc') ? 'desc' : 'asc';
    this.props.onSort(sortBy, sortOrder);
  };

  render() {
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
        <a
          onClick={this.handleSort}
          role="button"
          tabIndex={0}
        >
          <span>{ this.props.column.label }</span>
          <span className="sorting-indicator" />
        </a>
      );
    } else {
      label = this.props.column.label;
    }
    return (
      <th
        role="columnheader"
        className={classes}
        id={this.props.column.name}
        scope="col"
        width={this.props.column.width || null}
      >{label}</th>
    );
  }
}

ListingColumn.propTypes = {
  column: PropTypes.shape({
    name: PropTypes.string,
    sorted: PropTypes.string,
    is_primary: PropTypes.bool,
    sortable: PropTypes.bool,
    label: PropTypes.string,
    width: PropTypes.oneOfType([
      PropTypes.string,
      PropTypes.number,
    ]),
  }).isRequired,
  sort_by: PropTypes.string,
  onSort: PropTypes.func.isRequired,
};

ListingColumn.defaultProps = {
  sort_by: undefined,
};

export default ListingColumn;
