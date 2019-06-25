import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

class ListingSearch extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      search: '',
    };
    this.handleSearch = this.handleSearch.bind(this);
    this.onChange = this.onChange.bind(this);
  }

  componentDidUpdate(prevProps) {
    if (prevProps.search !== this.props.search) {
      setImmediate(() => {
        this.setState({ search: this.props.search });
      });
    }
  }

  onChange(e) {
    this.setState({ search: e.target.value });
  }

  handleSearch(e) {
    e.preventDefault();
    this.props.onSearch(
      this.state.search.trim()
    );
  }

  render() {
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
            name="s"
            onChange={this.onChange}
            value={this.state.search}
          />
          <input
            type="submit"
            value={MailPoet.I18n.t('searchLabel')}
            className="button"
          />
        </p>
      </form>
    );
  }
}

ListingSearch.propTypes = {
  search: PropTypes.string.isRequired,
  onSearch: PropTypes.func.isRequired,
};

export default ListingSearch;
