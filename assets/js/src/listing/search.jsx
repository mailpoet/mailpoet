import React from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import PropTypes from 'prop-types';
import Input from 'common/form/input/input.tsx';
import icon from './assets/search_icon.tsx';

class ListingSearch extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      search: '',
    };
    this.handleSearch = this.handleSearch.bind(this);
    this.debouncedHandleSearch = _.debounce(this.handleSearch, 300);
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
    this.debouncedHandleSearch();
  }

  handleSearch() {
    this.props.onSearch(
      this.state.search.trim()
    );
  }

  render() {
    if (this.props.search === false) {
      return false;
    }
    return (
      <div className="mailpoet-listing-search">
        <form name="search" onSubmit={(e) => { e.preventDefault(); this.handleSearch(); }}>
          <label htmlFor="search_input" className="screen-reader-text">
            {MailPoet.I18n.t('searchLabel')}
          </label>
          <Input
            dimension="small"
            iconStart={icon}
            type="search"
            id="search_input"
            name="s"
            onChange={this.onChange}
            value={this.state.search}
            placeholder={MailPoet.I18n.t('searchLabel')}
          />
        </form>
      </div>
    );
  }
}

ListingSearch.propTypes = {
  search: PropTypes.string.isRequired,
  onSearch: PropTypes.func.isRequired,
};

export default ListingSearch;
