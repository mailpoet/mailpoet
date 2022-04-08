import MailPoet from 'mailpoet';
import { Component } from 'react';
import PropTypes from 'prop-types';
import Checkbox from 'common/form/checkbox/checkbox.tsx';
import ListingColumn from './listing_column.jsx';

class ListingHeader extends Component {
  constructor(props) {
    super(props);
    this.handleSelectItems = this.handleSelectItems.bind(this);
  }

  handleSelectItems(evt) {
    this.props.onSelectItems(evt.target.checked);
  }

  render() {
    const columns = this.props.columns.map((column, index) => {
      const renderColumn = column;
      renderColumn.is_primary = index === 0;
      renderColumn.sorted =
        this.props.sort_by === column.name ? this.props.sort_order : 'desc';
      return (
        <ListingColumn
          onSort={this.props.onSort}
          sort_by={this.props.sort_by}
          key={`column-${column.name}`}
          column={renderColumn}
        />
      );
    });

    let checkbox;

    if (this.props.is_selectable === true) {
      checkbox = (
        <th className="manage-column column-cb mailpoet-listing-check-column">
          <label className="screen-reader-text" htmlFor="select_all">
            {MailPoet.I18n.t('selectAll')}
          </label>
          <Checkbox
            name="select_all"
            id="select_all"
            automationId="select_all"
            checked={this.props.selection}
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
  columns: PropTypes.arrayOf(PropTypes.object),
  sort_by: PropTypes.string,
  sort_order: PropTypes.string,
  is_selectable: PropTypes.bool.isRequired,
  selection: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]).isRequired,
};

ListingHeader.defaultProps = {
  columns: [],
  sort_by: undefined,
  sort_order: 'desc',
};

export default ListingHeader;
