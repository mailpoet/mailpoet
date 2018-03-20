define([
  'react',
  'jquery',
  'mailpoet',
],
(
  React,
  jQuery,
  MailPoet
) => {
  const ListingFilters = React.createClass({
    handleFilterAction: function () {
      const filters = {};
      this.getAvailableFilters().forEach((filter, i) => {
        filters[this.refs[`filter-${i}`].name] = this.refs[`filter-${i}`].value;
      });
      if (this.props.onBeforeSelectFilter) {
        this.props.onBeforeSelectFilter(filters);
      }
      return this.props.onSelectFilter(filters);
    },
    handleEmptyTrash: function () {
      return this.props.onEmptyTrash();
    },
    getAvailableFilters: function () {
      const filters = this.props.filters;
      return Object.keys(filters).filter(filter => !(
          filters[filter].length === 0
          || (
            filters[filter].length === 1
            && !filters[filter][0].value
          )
        ));
    },
    componentDidUpdate: function () {
      const selectedFilters = this.props.filter;
      this.getAvailableFilters().forEach(
        (filter, i) => {
          if (selectedFilters[filter] !== undefined && selectedFilters[filter]) {
            jQuery(this.refs[`filter-${i}`])
              .val(selectedFilters[filter])
              .trigger('change');
          }
        }
        );
    },
    render: function () {
      const filters = this.props.filters;
      const availableFilters = this.getAvailableFilters()
        .map((filter, i) => (
          <select
            ref={`filter-${i}`}
            key={`filter-${i}`}
            name={filter}
            >
            { filters[filter].map((option, j) => (
              <option
                value={option.value}
                key={`filter-option-${j}`}
                >{ option.label }</option>
                )) }
          </select>
          ));

      let button;

      if (availableFilters.length > 0) {
        button = (
          <input
            id="post-query-submit"
            onClick={this.handleFilterAction}
            type="submit"
            defaultValue={MailPoet.I18n.t('filter')}
            className="button" />
        );
      }

      let emptyTrash;
      if (this.props.group === 'trash') {
        emptyTrash = (
          <input
            onClick={this.handleEmptyTrash}
            type="submit"
            value={MailPoet.I18n.t('emptyTrash')}
            className="button"
          />
        );
      }

      return (
        <div className="alignleft actions actions">
          { availableFilters }
          { button }
          { emptyTrash }
        </div>
      );
    },
  });

  return ListingFilters;
});
