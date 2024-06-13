import { Component } from 'react';
import { __ } from '@wordpress/i18n';
import jQuery from 'jquery';
import PropTypes from 'prop-types';

import { Button } from 'common/button/button.tsx';
import { Select } from 'common/form/select/select.tsx';
import { withBoundary } from '../common';

class ListingFilters extends Component {
  componentDidUpdate() {
    const selectedFilters = this.props.filter;
    this.getAvailableFilters().forEach((filter, i) => {
      if (selectedFilters[filter] !== undefined && selectedFilters[filter]) {
        jQuery(this[`filter-${i}`])
          .val(selectedFilters[filter])
          .trigger('change');
      }
    });
  }

  getAvailableFilters = () => {
    const filters = this.props.filters;
    return Object.keys(filters).filter(
      (filter) =>
        !(
          filters[filter].length === 0 ||
          (filters[filter].length === 1 && !filters[filter][0].value)
        ),
    );
  };

  handleEmptyTrash = () => this.props.onEmptyTrash();

  handleFilterAction = () => {
    const { onSelectFilter, onBeforeSelectFilter = undefined } = this.props;
    const filters = {};
    this.getAvailableFilters().forEach((filter, i) => {
      filters[this[`filter-${i}`].name] = this[`filter-${i}`].value;
    });
    if (onBeforeSelectFilter) {
      onBeforeSelectFilter(filters);
    }
    return onSelectFilter(filters);
  };

  render() {
    const filters = this.props.filters;
    const availableFilters = this.getAvailableFilters().map((filter, i) => (
      <Select
        isMinWidth
        dimension="small"
        ref={(c) => {
          this[`filter-${i}`] = c;
        }}
        key={`filter-${filter}`}
        name={filter}
        automationId={`listing_filter_${filter}`}
        onChange={this.handleFilterAction}
      >
        {filters[filter].map((option) => (
          <option value={option.value} key={`filter-option-${option.value}`}>
            {option.label}
          </option>
        ))}
      </Select>
    ));

    let emptyTrash;
    if (this.props.group === 'trash') {
      emptyTrash = (
        <span className="mailpoet-listing-filters-empty-trash">
          <Button
            variant="secondary"
            onClick={this.handleEmptyTrash}
            automationId="empty_trash"
          >
            {__('Empty Trash', 'mailpoet')}
          </Button>
        </span>
      );
    }

    return (
      <div className="mailpoet-listing-filters">
        {availableFilters}
        {emptyTrash}
      </div>
    );
  }
}

ListingFilters.propTypes = {
  filters: PropTypes.oneOfType([PropTypes.object, PropTypes.array]).isRequired,
  onEmptyTrash: PropTypes.func.isRequired,
  onBeforeSelectFilter: PropTypes.func,
  onSelectFilter: PropTypes.func.isRequired,
  filter: PropTypes.objectOf(PropTypes.string).isRequired,
  group: PropTypes.string.isRequired,
};

ListingFilters.displayName = 'ListingFilters';
const ListingFiltersWithBoundary = withBoundary(ListingFilters);
export { ListingFiltersWithBoundary as ListingFilters };
