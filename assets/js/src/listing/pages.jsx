define([
    'react',
    'classnames',
    'mailpoet'
  ], (
    React,
    classNames,
    MailPoet
  ) => {

  const ListingPages = React.createClass({
    getInitialState: function () {
      return {
        page: null
      };
    },
    setPage: function (page) {
      this.setState({
        page: null
      }, () => {
        this.props.onSetPage(this.constrainPage(page));
      });
    },
    setFirstPage: function () {
      this.setPage(1);
    },
    setLastPage: function () {
      this.setPage(this.getLastPage());
    },
    setPreviousPage: function () {
      this.setPage(this.constrainPage(
        parseInt(this.props.page, 10) - 1)
      );
    },
    setNextPage: function () {
      this.setPage(this.constrainPage(
        parseInt(this.props.page, 10) + 1)
      );
    },
    constrainPage: function (page) {
      return Math.min(Math.max(1, Math.abs(~~page)), this.getLastPage());
    },
    handleSetManualPage: function (e) {
      if(e.which === 13) {
        this.setPage(this.state.page);
      }
    },
    handleChangeManualPage: function (e) {
      this.setState({
        page: e.target.value
      });
    },
    handleBlurManualPage: function (e) {
      this.setPage(e.target.value);
    },
    getLastPage: function () {
      return Math.ceil(this.props.count / this.props.limit);
    },
    render: function () {
      if(this.props.count === 0) {
        return false;
      } else {
        let pagination = false;
        let firstPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">«</span>
        );
        let previousPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">‹</span>
        );
        let nextPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">›</span>
        );
        let lastPage = (
          <span aria-hidden="true" className="tablenav-pages-navspan">»</span>
        );

        if(this.props.limit > 0 && this.props.count > this.props.limit) {
          if(this.props.page > 1) {
            previousPage = (
              <a href="javascript:;"
                onClick={ this.setPreviousPage }
                className="prev-page">
                <span className="screen-reader-text">{MailPoet.I18n.t('previousPage')}</span>
                <span aria-hidden="true">‹</span>
              </a>
            );
          }

          if(this.props.page > 2) {
            firstPage = (
              <a href="javascript:;"
                onClick={ this.setFirstPage }
                className="first-page">
                <span className="screen-reader-text">{MailPoet.I18n.t('firstPage')}</span>
                <span aria-hidden="true">«</span>
              </a>
            );
          }

          if(this.props.page < this.getLastPage()) {
            nextPage = (
              <a href="javascript:;"
                onClick={ this.setNextPage }
                className="next-page">
                <span className="screen-reader-text">{MailPoet.I18n.t('nextPage')}</span>
                <span aria-hidden="true">›</span>
              </a>
            );
          }

          if(this.props.page < this.getLastPage() - 1) {
            lastPage = (
              <a href="javascript:;"
                onClick={ this.setLastPage }
                className="last-page">
                <span className="screen-reader-text">{MailPoet.I18n.t('lastPage')}</span>
                <span aria-hidden="true">»</span>
              </a>
            );
          }

          let pageValue =  this.props.page;
          if(this.state.page !== null) {
            pageValue = this.state.page;
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
                  htmlFor="current-page-selector">{MailPoet.I18n.t('currentPage')}</label>
                <input
                  type="text"
                  onChange={ this.handleChangeManualPage }
                  onKeyUp={ this.handleSetManualPage }
                  onBlur={ this.handleBlurManualPage }
                  aria-describedby="table-paging"
                  size="2"
                  ref="page"
                  value={ pageValue }
                  name="paged"
                  id="current-page-selector"
                  className="current-page" />
                &nbsp;{MailPoet.I18n.t('pageOutOf')}&nbsp;
                <span className="total-pages">
                  {Math.ceil(this.props.count / this.props.limit).toLocaleString()}
                </span>
              </span>
              &nbsp;
              {nextPage}
              &nbsp;
              {lastPage}
            </span>
          );
        }

        const classes = classNames(
          'tablenav-pages',
          { 'one-page': (this.props.count <= this.props.limit) }
        );

        let numberOfItemsLabel;
        if (this.props.count == 1) {
          numberOfItemsLabel = MailPoet.I18n.t('numberOfItemsSingular');
        } else {
          numberOfItemsLabel = MailPoet.I18n.t('numberOfItemsMultiple')
            .replace('%$1d', this.props.count.toLocaleString());
        }
        return (
          <div className={ classes }>
            <span className="displaying-num">{ numberOfItemsLabel }</span>
            { pagination }
          </div>
        );
      }
    }
  });

  return ListingPages;
});
