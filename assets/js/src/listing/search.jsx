define([
  'mailpoet',
  'react',
], (
    MailPoet,
    React
  ) => {
  const ListingSearch = React.createClass({
    handleSearch: function handleSearch(e) {
      e.preventDefault();
      this.props.onSearch(
        this.search.value.trim()
      );
    },
    componentWillReceiveProps: function componentWillReceiveProps(nextProps) {
      this.search.value = nextProps.search;
    },
    render: function render() {
      if (this.props.search === false) {
        return false;
      }
      return (
        <form name="search" onSubmit={this.handleSearch}>
          <p className="search-box">
            <label htmlFor="search_input" className="screen-reader-text">
              {MailPoet.I18n.t('searchLabel')}
            </label>
            <input
              type="search"
              id="search_input"
              ref={(c) => { this.search = c; }}
              name="s"
              defaultValue={this.props.search}
            />
            <input
              type="submit"
              defaultValue={MailPoet.I18n.t('searchLabel')}
              className="button"
            />
          </p>
        </form>
      );
    },
  });

  return ListingSearch;
});
