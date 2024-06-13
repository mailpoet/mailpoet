import { Component } from 'react';
import classnames from 'classnames';
import PropTypes from 'prop-types';

class ListingColumn extends Component {
  handleSort = () => {
    const sortBy = this.props.column.name;
    const sortOrder = this.props.column.sorted === 'asc' ? 'desc' : 'asc';
    this.props.onSort(sortBy, sortOrder);
  };

  render() {
    const { column, sort_by: sortBy = undefined } = this.props;
    const classes = classnames(
      'manage-column',
      { 'column-primary': column.is_primary },
      { sortable: column.sortable },
      column.sorted,
      { sorted: sortBy === column.name },
      column.className,
    );
    let label;

    if (column.sortable === true) {
      label = (
        <a
          onClick={this.handleSort}
          role="button"
          tabIndex={0}
          onKeyDown={(event) => {
            if (
              ['keydown', 'keypress'].includes(event.type) &&
              ['Enter', ' '].includes(event.key)
            ) {
              event.preventDefault();
              this.handleSort();
            }
          }}
        >
          <span>{column.label}</span>
          <span className="mailpoet-listing-sorting-arrow" />
        </a>
      );
    } else {
      label = column.label;
    }
    return (
      <th
        role="columnheader"
        className={classes}
        id={column.name}
        scope="col"
        width={column.width || null}
        data-automation-id={`listing-column-header-${column.name}`}
      >
        {label}
      </th>
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
    width: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    className: PropTypes.string,
  }).isRequired,
  sort_by: PropTypes.string,
  onSort: PropTypes.func.isRequired,
};

export { ListingColumn };
