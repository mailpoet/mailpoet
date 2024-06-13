import { Component } from 'react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
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
    const { count, limit, page, position = '' } = this.props;
    if (count === 0) {
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

    if (limit > 0 && count > limit) {
      if (page > 1) {
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
              {__('Previous page', 'mailpoet')}
            </span>
            <span aria-hidden="true">
              <Arrow direction="left" />
            </span>
          </a>
        );
      }

      if (page > 2) {
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
              {__('First page', 'mailpoet')}
            </span>
            <span aria-hidden="true">
              <Arrow direction="left" />
              <Arrow direction="left" />
            </span>
          </a>
        );
      }

      if (page < this.getLastPage()) {
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
              {__('Next page', 'mailpoet')}
            </span>
            <span aria-hidden="true">
              <Arrow />
            </span>
          </a>
        );
      }

      if (page < this.getLastPage() - 1) {
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
              {__('Last page', 'mailpoet')}
            </span>
            <span aria-hidden="true">
              <Arrow />
              <Arrow />
            </span>
          </a>
        );
      }

      let pageValue = page;
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
              htmlFor={`current-page-selector-${position}`}
            >
              {__('Current page', 'mailpoet')}
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
              id={`current-page-selector-${position}`}
              className="mailpoet-listing-current-page"
            />
            {__('of', 'mailpoet')}
            &nbsp;
            <span className="mailpoet-listing-total-pages">
              {Math.ceil(count / limit).toLocaleString()}
            </span>
          </span>
          &nbsp;
          {nextPage}
          &nbsp;
          {lastPage}
        </span>
      );
    }

    const classes = classnames('mailpoet-listing-pages', {
      'one-page': count <= limit,
    });

    let numberOfItemsLabel;
    if (Number(count) === 1) {
      numberOfItemsLabel = __('1 item', 'mailpoet');
    } else {
      numberOfItemsLabel = __('%1$d items', 'mailpoet').replace(
        '%1$d',
        parseInt(count, 10).toLocaleString(),
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

/* type ArrowProps = {
  direction?: 'right',
  disabled?: boolean
} */

function Arrow({ direction = 'right', disabled = false }) {
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

export { ListingPages };
