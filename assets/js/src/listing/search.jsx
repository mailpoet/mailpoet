define('search', ['react'], function(React) {
  /*
    props:
      onSearch  -> callback(search)
      search    -> string
  */
  var ListingSearch = React.createClass({
    handleSearch: function(e) {
      e.preventDefault();
      this.props.onSearch(
        this.refs.search.getDOMNode().value
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
              ref="search"
              defaultValue={this.props.search} />
            <input
              type="submit"
              defaultValue="Search"
              className="button" />
          </p>
        </form>
      );
    }
  });

  return ListingSearch;
});
