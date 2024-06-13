import { Component } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

import { Checkbox } from 'common/form/checkbox/checkbox.tsx';
import { ListingColumn } from './listing-column.jsx';

class ListingHeader extends Component {
  constructor(props) {
    super(props);
    this.handleSelectItems = this.handleSelectItems.bind(this);
  }

  handleSelectItems(evt) {
    this.props.onSelectItems(evt.target.checked);
  }

  render() {
    const {
      onSort,
      sort_by: sortBy,
      sort_order: sortOrder,
      is_selectable: isSelectable,
      selection,
      columns: propsColumns = [],
    } = this.props;
    const columns = propsColumns.map((column, index) => {
      const renderColumn = column;
      renderColumn.is_primary = index === 0;
      renderColumn.sorted = sortBy === column.name ? sortOrder : 'desc';
      return (
        <ListingColumn
          onSort={onSort}
          sort_by={sortBy}
          key={`column-${column.name}`}
          column={renderColumn}
        />
      );
    });

    let checkbox;

    if (isSelectable === true) {
      checkbox = (
        <th className="manage-column column-cb mailpoet-listing-check-column">
          <label className="screen-reader-text" htmlFor="select_all">
            {__('Select All', 'mailpoet')}
          </label>
          <Checkbox
            name="select_all"
            id="select_all"
            automationId="select_all"
            checked={selection}
            onCheck={() => {}}
            onChange={this.handleSelectItems}
          />
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
}

ListingHeader.propTypes = {
  onSelectItems: PropTypes.func.isRequired,
  onSort: PropTypes.func.isRequired,
  columns: PropTypes.arrayOf(PropTypes.object), // eslint-disable-line react/forbid-prop-types
  sort_by: PropTypes.string,
  sort_order: PropTypes.string,
  is_selectable: PropTypes.bool.isRequired,
  selection: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]).isRequired,
};

export { ListingHeader };
