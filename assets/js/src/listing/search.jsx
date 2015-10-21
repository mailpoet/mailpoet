define(['react'], function(React) {

  var ListingSearch = React.createClass({
    handleSearch: function(e) {
      e.preventDefault();
      this.props.onSearch(
        this.refs.search.value
      );
    },
    render: function() {
      return (
        <form name="search" onSubmit={this.handleSearch}>
          <p className="search-box">
            <label htmlFor="search_input" className="screen-reader-text">
              Search
            </label>
            <input
              type="search"
              id="search_input"
              ref="search"
              name="s"
              defaultValue={this.props.search} />
            <input
              type="submit"
              defaultValue={MailPoetI18n.searchLabel}
              className="button" />
          </p>
        </form>
      );
    }
  });

  return ListingSearch;
});
