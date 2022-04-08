import { Component } from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import PropTypes from 'prop-types';
import Input from 'common/form/input/input.tsx';
import icon from './assets/search_icon.tsx';

/**
 * this components has to be setup with router
 * how it works right now:
 *   1) On first render it ignores all props and renders '' using state.search <-- that is a bug
 *      Why is it a bug?
 *        Because if this is used without a router and props are sent in, they are ignored
 *   2) On second and all other renders it gets props in `componentDidUpdate` and
 *        updates state and renders properly.
 * what to do with this file?
 *   we need to fix it so that it renders properly even on the first render.
 * we need to refactor it to a functional component and typescript first and
 *   then use `useState` with `useEffect` to make it working. Something like this:
 *
 *  const [search, setSearch] = useState(props.search);
 *  useEffect(() => {
 *   setSearch(props.search)
 *  }, [props.search]);
 */
class ListingSearch extends Component {
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

  handleSearch() {
    this.props.onSearch(this.state.search.trim());
  }

  onChange(e) {
    this.setState({ search: e.target.value });
    this.debouncedHandleSearch();
  }

  render() {
    if (this.props.search === false) {
      return false;
    }
    return (
      <div className="mailpoet-listing-search">
        <form
          name="search"
          onSubmit={(e) => {
            e.preventDefault();
            this.handleSearch();
          }}
        >
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
