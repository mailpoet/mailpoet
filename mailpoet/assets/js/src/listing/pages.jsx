import { Component } from 'react';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

class ListingPages extends Component {
  constructor(props) {
    super(props);
    this.state = {
      page: null,
    };
  }

  setPage = (page) => {
    this.setState(
      {
        page: null,
      },
      () => {
        this.props.onSetPage(this.constrainPage(page));
      },
    );
  };

  setFirstPage = () => {
    this.setPage(1);
  };

  setLastPage = () => {
    this.setPage(this.getLastPage());
  };

  setPreviousPage = () => {
    this.setPage(this.constrainPage(parseInt(this.props.page, 10) - 1));
  };

  setNextPage = () => {
    this.setPage(this.constrainPage(parseInt(this.props.page, 10) + 1));
  };

  getLastPage = () => Math.ceil(this.props.count / this.props.limit);

  handleSetManualPage = (e) => {
    if (e.which === 13) {
      this.setPage(this.state.page);
    }
  };

  handleChangeManualPage = (e) => {
    this.setState({
      page: e.target.value,
    });
  };

  handleBlurManualPage = (e) => {
    this.setPage(e.target.value);
  };

  constrainPage = (page) =>
    Math.min(Math.max(1, Math.abs(Number(page))), this.getLastPage());

  render() {
    if (this.props.count === 0) {
      return false;
    }
    let pagination = false;
    let firstPage = (
      <span aria-hidden="true" className="mailpoet-listing-pages-first">
        <Arrow direction="left" disabled />
        <Arrow direction="left" disabled />
      </span>
    );
    let previousPage = (
      <span aria-hidden="true" className="mailpoet-listing-pages-previous">
        <Arrow direction="left" disabled />
      </span>
    );
    let nextPage = (
      <span aria-hidden="true" className="mailpoet-listing-pages-next">
        <Arrow disabled />
      </span>
    );
    let lastPage = (
      <span aria-hidden="true" className="mailpoet-listing-pages-last">
        <Arrow disabled />
        <Arrow disabled />
      </span>
    );

    if (this.props.limit > 0 && this.props.count > this.props.limit) {
      if (this.props.page > 1) {
        previousPage = (
          <a
            href="#"
            onClick={(event) => {
              event.preventDefault();
              this.setPreviousPage(event);
            }}
            className="mailpoet-listing-pages-previous"
          >
            <span className="screen-reader-text">
              {MailPoet.I18n.t('previousPage')}
            </span>
            <span aria-hidden="true">
              <Arrow direction="left" />
            </span>
          </a>
        );
      }

      if (this.props.page > 2) {
        firstPage = (
          <a
            href="#"
            onClick={(event) => {
              event.preventDefault();
              this.setFirstPage(event);
            }}
            className="mailpoet-listing-pages-first"
          >
            <span className="screen-reader-text">
              {MailPoet.I18n.t('firstPage')}
            </span>
            <span aria-hidden="true">
              <Arrow direction="left" />
              <Arrow direction="left" />
            </span>
          </a>
        );
      }

      if (this.props.page < this.getLastPage()) {
        nextPage = (
          <a
            href="#"
            onClick={(event) => {
              event.preventDefault();
              this.setNextPage(event);
            }}
            className="mailpoet-listing-pages-next"
          >
            <span className="screen-reader-text">
              {MailPoet.I18n.t('nextPage')}
            </span>
            <span aria-hidden="true">
              <Arrow />
            </span>
          </a>
        );
      }

      if (this.props.page < this.getLastPage() - 1) {
        lastPage = (
          <a
            href="#"
            onClick={(event) => {
              event.preventDefault();
              this.setLastPage();
            }}
            className="mailpoet-listing-pages-last"
          >
            <span className="screen-reader-text">
              {MailPoet.I18n.t('lastPage')}
            </span>
            <span aria-hidden="true">
              <Arrow />
              <Arrow />
            </span>
          </a>
        );
      }

      let pageValue = this.props.page;
      if (this.state.page !== null) {
        pageValue = this.state.page;
      }

      pagination = (
        <span className="mailpoet-listing-pages-links">
          {firstPage}
          &nbsp;
          {previousPage}
          &nbsp;
          <span className="mailpoet-listing-paging-input">
            <label
              className="screen-reader-text"
              htmlFor={`current-page-selector-${this.props.position}`}
            >
              {MailPoet.I18n.t('currentPage')}
            </label>
            <input
              type="text"
              onChange={this.handleChangeManualPage}
              onKeyUp={this.handleSetManualPage}
              onBlur={this.handleBlurManualPage}
              aria-describedby="table-paging"
              size="2"
              value={pageValue}
              name="paged"
              id={`current-page-selector-${this.props.position}`}
              className="mailpoet-listing-current-page"
            />
            {MailPoet.I18n.t('pageOutOf')}
            &nbsp;
            <span className="mailpoet-listing-total-pages">
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

    const classes = classNames('mailpoet-listing-pages', {
      'one-page': this.props.count <= this.props.limit,
    });

    let numberOfItemsLabel;
    if (Number(this.props.count) === 1) {
      numberOfItemsLabel = MailPoet.I18n.t('numberOfItemsSingular');
    } else {
      numberOfItemsLabel = MailPoet.I18n.t('numberOfItemsMultiple').replace(
        '%1$d',
        parseInt(this.props.count, 10).toLocaleString(),
      );
    }

    return (
      <div className={classes}>
        <span className="mailpoet-listing-pages-num">{numberOfItemsLabel}</span>
        {pagination}
      </div>
    );
  }
}

ListingPages.propTypes = {
  position: PropTypes.string,
  onSetPage: PropTypes.func.isRequired,
  page: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
  count: PropTypes.number.isRequired,
  limit: PropTypes.number.isRequired,
};

ListingPages.defaultProps = {
  position: '',
};

/* type ArrowProps = {
  direction?: 'right',
  disabled?: boolean
} */

function Arrow({ direction, disabled }) {
  const arrowLeftPath =
    'M8 10V2c0-.552-.448-1-1-1-.216 0-.427.07-.6.2l-5.333 4c-.442.331-.532.958-.2 1.4.057.076.124.143.2.2l5.333 4c.442.331 1.069.242 1.4-.2.13-.173.2-.384.2-.6z';
  const arrowRightPath =
    'M0 10V2c0-.552.448-1 1-1 .216 0 .427.07.6.2l5.333 4c.442.331.532.958.2 1.4-.057.076-.124.143-.2.2l-5.333 4c-.442.331-1.069.242-1.4-.2-.13-.173-.2-.384-.2-.6z';
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="8"
      height="12"
      viewBox="0 0 8 12"
    >
      <path
        fill={disabled ? '#E5E9F8' : '#9CA6CC'}
        d={direction === 'left' ? arrowLeftPath : arrowRightPath}
      />
    </svg>
  );
}

Arrow.propTypes = {
  direction: PropTypes.oneOf(['left', 'right']),
  disabled: PropTypes.bool,
};

Arrow.defaultProps = {
  direction: 'right',
  disabled: false,
};

export default ListingPages;
