define(['react', 'classnames'], function(React, classNames) {

  var ListingPages = React.createClass({
    setFirstPage: function() {
      this.props.onSetPage(1);
    },
    setLastPage: function() {
      this.props.onSetPage(this.getLastPage());
    },
    setPreviousPage: function() {
      this.props.onSetPage(this.constrainPage(this.props.page - 1));
    },
    setNextPage: function() {
      this.props.onSetPage(this.constrainPage(this.props.page + 1));
    },
    constrainPage: function(page) {
      return Math.min(Math.max(1, Math.abs(~~page)), this.getLastPage());
    },
    handleSetPage: function() {
      this.props.onSetPage(
        this.constrainPage(this.refs.page.getDOMNode().value)
      );
    },
    getLastPage: function() {
      return Math.ceil(this.props.count / this.props.limit);
    },
    render: function() {
      if(this.props.count === 0) {
        return (<div></div>);
      } else {
        var pagination,
        firstPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">«</span>
        ),
        previousPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">‹</span>
        ),
        nextPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">›</span>
        ),
        lastPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">»</span>
        );

        if(this.props.count > this.props.limit) {
          if(this.props.page > 1) {
            previousPage = (
              <a href="javascript:;"
                onClick={this.setPreviousPage}
                className="prev-page">
                <span className="screen-reader-text">Previous page</span>
                <span aria-hidden="true">‹</span>
              </a>
            );
          }

          if(this.props.page > 2) {
            firstPage = (
              <a href="javascript:;"
                onClick={this.setFirstPage}
                className="first-page">
                <span className="screen-reader-text">First page</span>
                <span aria-hidden="true">«</span>
              </a>
            );
          }

          if(this.props.page < this.getLastPage()) {
            nextPage = (
              <a href="javascript:;"
                onClick={this.setNextPage}
                className="next-page">
                <span className="screen-reader-text">Next page</span>
                <span aria-hidden="true">›</span>
              </a>
            );
          }

          if(this.props.page < this.getLastPage() - 1) {
            lastPage = (
              <a href="javascript:;"
                onClick={this.setLastPage}
                className="last-page">
                <span className="screen-reader-text">Last page</span>
                <span aria-hidden="true">»</span>
              </a>
            );
          }

          pagination = (
            <span className="pagination-links">
              {firstPage}
              {previousPage}
              &nbsp;
              <span className="paging-input">
                <label
                  className="screen-reader-text"
                  htmlFor="current-page-selector">Current Page</label>
                <input
                  type="text"
                  onChange={this.handleSetPage}
                  aria-describedby="table-paging"
                  size="1"
                  ref="page"
                  value={this.props.page}
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
              {lastPage}
            </span>
          );
        }

        var classes = classNames(
          'tablenav-pages',
          { 'one-page': (this.props.count <= this.props.limit) }
        );

        return (
          <div className={classes}>
            <span className="displaying-num">{ this.props.count } item(s)</span>
            { pagination }
          </div>
        );
      }
    }
  });

  return ListingPages;
});