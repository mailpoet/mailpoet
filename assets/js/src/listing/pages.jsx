define(['react', 'classnames'], function(React, classNames) {

  var ListingPages = React.createClass({
    getInitialState: function() {
      return {
        page: null
      }
    },
    setPage: function(page) {
      this.props.onSetPage(page);
    },
    setFirstPage: function() {
      this.setPage(1);
    },
    setLastPage: function() {
      this.setPage(this.getLastPage());
    },
    setPreviousPage: function() {
      this.setPage(this.constrainPage(this.props.page - 1));
    },
    setNextPage: function() {
      this.setPage(this.constrainPage(this.props.page + 1));
    },
    constrainPage: function(page) {
      return Math.min(Math.max(1, Math.abs(~~page)), this.getLastPage());
    },
    handleSetManualPage: function(e) {
      if(e.which === 13) {
        this.setPage(this.state.page);
        this.setState({ page: null });
      }
    },
    handleChangeManualPage: function(e) {
      this.setState({
        page: this.constrainPage(e.target.value)
      });
    },
    getLastPage: function() {
      return Math.ceil(this.props.count / this.props.limit);
    },
    render: function() {
      if(this.props.count === 0) {
        return false;
      } else {
        var pagination = false;
        var firstPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">«</span>
        );
        var previousPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">‹</span>
        );
        var nextPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">›</span>
        );
        var lastPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">»</span>
        );

        if(this.props.limit > 0 && this.props.count > this.props.limit) {
          if(this.props.page > 1) {
            previousPage = (
              <a href="javascript:;"
                onClick={ this.setPreviousPage }
                className="prev-page">
                <span className="screen-reader-text">Previous page</span>
                <span aria-hidden="true">‹</span>
              </a>
            );
          }

          if(this.props.page > 2) {
            firstPage = (
              <a href="javascript:;"
                onClick={ this.setFirstPage }
                className="first-page">
                <span className="screen-reader-text">First page</span>
                <span aria-hidden="true">«</span>
              </a>
            );
          }

          if(this.props.page < this.getLastPage()) {
            nextPage = (
              <a href="javascript:;"
                onClick={ this.setNextPage }
                className="next-page">
                <span className="screen-reader-text">Next page</span>
                <span aria-hidden="true">›</span>
              </a>
            );
          }

          if(this.props.page < this.getLastPage() - 1) {
            lastPage = (
              <a href="javascript:;"
                onClick={ this.setLastPage }
                className="last-page">
                <span className="screen-reader-text">Last page</span>
                <span aria-hidden="true">»</span>
              </a>
            );
          }

          pagination = (
            <span className="pagination-links">
              {firstPage}
              &nbsp;
              {previousPage}
              &nbsp;
              <span className="paging-input">
                <label
                  className="screen-reader-text"
                  htmlFor="current-page-selector">Current Page</label>
                <input
                  type="text"
                  onChange={ this.handleChangeManualPage }
                  onKeyUp={ this.handleSetManualPage }
                  aria-describedby="table-paging"
                  size="1"
                  ref="page"
                  value={ this.state.page || this.props.page }
                  name="paged"
                  id="current-page-selector"
                  className="current-page" />
                &nbsp;of&nbsp;
                <span className="total-pages">
                  {Math.ceil(this.props.count / this.props.limit)}
                </span>
              </span>
              &nbsp;
              {nextPage}
              &nbsp;
              {lastPage}
            </span>
          );
        }

        var classes = classNames(
          'tablenav-pages',
          { 'one-page': (this.props.count <= this.props.limit) }
        );

        return (
          <div className={ classes }>
            <span className="displaying-num">{ this.props.count } items</span>
            { pagination }
          </div>
        );
      }
    }
  });

  return ListingPages;
});