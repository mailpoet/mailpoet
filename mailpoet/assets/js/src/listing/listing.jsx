import jQuery from 'jquery';
import { Component } from 'react';
import { __ } from '@wordpress/i18n';
import _ from 'underscore';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { Categories } from 'common/categories/categories';
import { ListingHeader } from 'listing/header.jsx';
import { ListingPages } from 'listing/pages.jsx';
import { ListingSearch } from 'listing/search.jsx';
import { ListingFilters } from 'listing/filters.jsx';
import { ListingItems } from 'listing/listing-items.jsx';
import { MailerError } from 'notices/mailer-error';
import { withRouter } from 'react-router-dom';
import { GlobalContext } from 'context';
import { withBoundary } from '../common';

class ListingComponent extends Component {
  constructor(props) {
    super(props);
    this.state = this.getEmptyState();
  }

  componentDidMount() {
    const { params: propsParams, auto_refresh: autoRefresh = false } =
      this.props;
    this.isComponentMounted = true;
    const params = propsParams || {};
    this.initWithParams(params);

    if (autoRefresh) {
      jQuery(document).on('heartbeat-tick.mailpoet', () => {
        this.getItems();
      });
    }
  }

  componentDidUpdate(prevProps) {
    const params = this.props.params || {};
    const prevParams = prevProps.params || {};
    if (!_.isEqual(params, prevParams)) {
      this.initWithParams(params);
    }
  }

  componentWillUnmount() {
    this.isComponentMounted = false;
  }

  getEmptyState = () => ({
    loading: false,
    search: '',
    page: 1,
    count: 0,
    limit: 10,
    sort_by: null,
    sort_order: null,
    items: [],
    groups: [],
    group: 'all',
    filters: {},
    filter: {},
    selected_ids: [],
    selection: false,
    meta: {},
  });

  setParams = () => {
    const { history, location = undefined } = this.props;
    if (location) {
      const params = Object.keys(this.state)
        .filter(
          (key) =>
            [
              'group',
              'filter',
              'search',
              'page',
              'sort_by',
              'sort_order',
            ].indexOf(key) !== -1,
        )
        .map((key) => {
          let value = this.state[key];
          if (value === Object(value)) {
            value = jQuery.param(value);
          } else if (value === Boolean(value)) {
            value = value.toString();
          }
          return {
            key,
            value,
          };
        })
        .filter(({ value }) => value !== '' && value !== null)
        .map(({ key, value }) => `${key}[${value}]`)
        .join('/');

      // set url
      const url = this.getUrlWithParams(params);

      if (location.pathname !== url) {
        history.push(`${url}`);
      }
    }
  };

  getUrlWithParams = (params) => {
    let { base_url: baseUrl = null } = this.props;
    if (baseUrl) {
      baseUrl = this.setBaseUrlParams(baseUrl);
      return `/${baseUrl}/${params}`;
    }
    return `/${params}`;
  };

  setBaseUrlParams = (baseUrl) => {
    let ret = baseUrl;
    if (ret.indexOf(':') !== -1) {
      const params = this.getParams();
      Object.keys(params).forEach((key) => {
        if (ret.indexOf(`:${key}`) !== -1) {
          ret = ret.replace(`:${key}`, params[key]);
        }
      });
    }

    return ret;
  };

  getParams = () => {
    const { params: propsParams, type = undefined } = this.props;
    const params = _.omit(propsParams, 'splat');
    // TODO:
    // find a way to set the "type" in the routes definition
    // so that it appears in `this.props.params`
    if (type) {
      params.type = type;
    }
    return params;
  };

  getParam = (param) => {
    const regex = /(.*)\[(.*)\]/;
    const matches = regex.exec(param);
    if (!matches) return null;
    return [matches[1], matches[2]];
  };

  getItems = () => {
    if (!this.isComponentMounted) return;

    const { endpoint, afterGetItems = undefined } = this.props;

    this.setState({ loading: true });
    this.clearSelection();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'listing',
      data: {
        params: this.getParams(),
        offset: (this.state.page - 1) * this.state.limit,
        limit: this.state.limit,
        group: this.state.group,
        filter: this.state.filter,
        search: this.state.search,
        sort_by: this.state.sort_by,
        sort_order: this.state.sort_order,
      },
    })
      .always(() => {
        if (!this.isComponentMounted) return;
        this.setState({ loading: false });
      })
      .done((response) => {
        if (!this.isComponentMounted) return;
        this.setState(
          {
            items: response.data || [],
            filters: response.meta.filters || {},
            groups: response.meta.groups || [],
            count: response.meta.count || 0,
            meta: _.omit(response.meta, ['filters', 'groups', 'count']),
          },
          () => {
            // if viewing an empty trash
            if (this.state.group === 'trash' && response.meta.count === 0) {
              // redirect to default group
              this.handleGroup('all');
            }

            // trigger afterGetItems callback if specified
            if (typeof afterGetItems === 'function') {
              afterGetItems(this.state);
            }
          },
        );
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
        }
      });
  };

  initWithParams = (params) => {
    const {
      limit = 10,
      sort_by: sortBy = null,
      sort_order: sortOrder = undefined,
    } = this.props;
    const state = this.getEmptyState();
    // check for url params
    _.mapObject(params, (param) => {
      if (!param) return;
      param.split('/').forEach((item) => {
        if (!item) return;
        const parsedParam = this.getParam(item);
        if (!parsedParam) return;
        const [key, value] = parsedParam;
        const filters = {};
        switch (key) {
          case 'filter':
            value.split('&').forEach((pair) => {
              const [k, v] = pair.split('=');
              filters[k] = v;
            });

            state.filter = filters;
            break;
          default:
            state[key] = value;
        }
      });
    });

    // limit per page
    if (limit !== undefined) {
      state.limit = Math.abs(Number(limit));
    }

    // sort by
    if (state.sort_by === null && sortBy !== undefined) {
      state.sort_by = sortBy;
    }

    // sort order
    if (state.sort_order === null && sortOrder !== undefined) {
      state.sort_order = sortOrder;
    }

    this.setState(state, () => {
      this.getItems();
    });
  };

  handleRestoreItem = (id) => {
    const { endpoint, messages = undefined } = this.props;
    this.setState({
      loading: true,
      page: 1,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'restore',
      data: {
        id,
      },
    })
      .done((response) => {
        if (messages !== undefined && messages.onRestore !== undefined) {
          messages.onRestore(response);
        }
        this.getItems();
      })
      .fail((response) => {
        this.context.notices.apiError(response, { scroll: true });
      });
  };

  handleTrashItem = (id) => {
    const { endpoint, messages = undefined } = this.props;
    this.setState({
      loading: true,
      page: 1,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'trash',
      data: {
        id,
      },
    })
      .done((response) => {
        if (messages !== undefined && messages.onTrash !== undefined) {
          messages.onTrash(response);
        }
        this.getItems();
      })
      .fail((response) => {
        this.context.notices.apiError(response, { scroll: true });
        this.setState({ loading: false });
      });
  };

  handleDeleteItem = (id) => {
    const { endpoint, messages = undefined } = this.props;
    this.setState({
      loading: true,
      page: 1,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'delete',
      data: {
        id,
      },
    })
      .done((response) => {
        if (messages !== undefined && messages.onDelete !== undefined) {
          messages.onDelete(response);
        }
        this.getItems();
      })
      .fail((response) => {
        this.context.notices.apiError(response, { scroll: true });
      });
  };

  handleEmptyTrash = () =>
    this.handleBulkAction('all', {
      action: 'delete',
      group: 'trash',
    })
      .done((response) => {
        const { messages = undefined } = this.props;
        if (messages !== undefined && messages.onDelete !== undefined) {
          messages.onDelete(response);
        }
        // redirect to default group
        this.handleGroup('all');
      })
      .fail((response) => {
        this.context.notices.apiError(response, { scroll: true });
      });

  handleBulkAction = (selectedIds, params) => {
    if (
      this.state.selection === false &&
      this.state.selected_ids.length === 0 &&
      selectedIds !== 'all'
    ) {
      return false;
    }
    const { endpoint } = this.props;

    this.setState({ loading: true });

    const data = params || {};
    data.listing = {
      params: this.getParams(),
      offset: 0,
      limit: 0,
      filter: this.state.filter,
      group: this.state.group,
      search: this.state.search,
    };
    if (selectedIds !== 'all') {
      data.listing.selection = selectedIds;
    }

    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'bulkAction',
      data,
    })
      .done(() => {
        // Reload items after a bulk action except for empty trash action which redirects to All tab.
        const isEmptyTrashAction =
          selectedIds === 'all' &&
          params.group === 'trash' &&
          params.action === 'delete';
        if (!isEmptyTrashAction) {
          this.getItems();
        }
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.apiError(response, { scroll: true });
        }
      });
  };

  handleSearch = (search) => {
    this.setState(
      {
        search,
        page: 1,
        selection: false,
        selected_ids: [],
      },
      () => {
        this.setParams();
      },
    );
  };

  handleSort = (sortBy, sortOrder = 'asc') => {
    this.setState(
      {
        sort_by: sortBy,
        sort_order: sortOrder === 'asc' ? 'asc' : 'desc',
      },
      () => {
        this.setParams();
      },
    );
  };

  handleSelectItem = (id, isChecked) => {
    this.setState((prevState) => {
      let selectedIds = prevState.selected_ids;
      let selection = false;

      if (isChecked) {
        selectedIds = jQuery.merge(selectedIds, [id]);
        // check whether all items on the page are selected
        if (
          jQuery('tbody .mailpoet-listing-check-column :checkbox:not(:checked)')
            .length === 0
        ) {
          selection = 'page';
        }
      } else {
        selectedIds.splice(selectedIds.indexOf(id), 1);
      }

      return {
        selection,
        selected_ids: selectedIds,
      };
    });
  };

  handleSelectItems = (isChecked) => {
    if (isChecked === false) {
      this.clearSelection();
    } else {
      this.setState((prevState) => {
        const selectedIds = prevState.items.map((item) => Number(item.id));

        return {
          selected_ids: selectedIds,
          selection: 'page',
        };
      });
    }
  };

  handleSelectAll = () => {
    if (this.state.selection === 'all') {
      this.clearSelection();
    } else {
      this.setState({
        selection: 'all',
        selected_ids: [],
      });
    }
  };

  clearSelection = () => {
    this.setState({
      selection: false,
      selected_ids: [],
    });
  };

  handleFilter = (filters) => {
    this.setState(
      {
        filter: filters,
        page: 1,
      },
      () => {
        this.setParams();
      },
    );
  };

  handleGroup = (group) => {
    // reset search
    jQuery('#search_input').val('');

    this.setState(
      {
        group,
        page: 1,
      },
      () => {
        this.setParams();
      },
    );
  };

  handleSetPage = (page) => {
    this.setState(
      {
        page,
        selection: false,
        selected_ids: [],
      },
      () => {
        this.setParams();
      },
    );
  };

  handleRenderItem = (item, actions) => {
    const { onRenderItem } = this.props;
    const render = onRenderItem(item, actions, this.state.meta);
    return render.props.children;
  };

  handleRefreshItems = () => {
    this.getItems();
  };

  render() {
    const items = this.state.items;
    const sortBy = this.state.sort_by;
    const sortOrder = this.state.sort_order;

    const {
      className = undefined,
      groups: propsGroups = true,
      getListingItemKey = undefined,
      isItemDeletable = () => true,
      isItemInactive = () => false,
      isItemToggleable = () => false,
      location = undefined,
      messages: propsMessages = undefined,
      renderExtraActions = undefined,
      onBeforeSelectFilter = undefined,
      search: propsSearch = true,
    } = this.props;

    // columns
    let columns = this.props.columns || [];
    columns = columns.filter(
      (column) => column.display === undefined || !!column.display === true,
    );

    // bulk actions
    let bulkActions = this.props.bulk_actions || [];

    if (this.state.group === 'trash' && bulkActions.length > 0) {
      bulkActions = [
        {
          name: 'restore',
          label: __('Restore', 'mailpoet'),
          onSuccess: this.props.messages.onRestore,
        },
        {
          name: 'delete',
          label: __('Delete permanently', 'mailpoet'),
          onSuccess: this.props.messages.onDelete,
        },
      ];
    }

    // item actions
    const itemActions = this.props.item_actions || [];

    const tableClasses = classnames('mailpoet-listing-table', {
      'mailpoet-listing-loading': this.state.loading,
    });

    // search
    let search = (
      <ListingSearch onSearch={this.handleSearch} search={this.state.search} />
    );
    if (propsSearch === false) {
      search = false;
    }

    const categories = this.state.groups
      .map((group) =>
        Object.assign(group, {
          automationId: `filters_${group.label
            .replace(' ', '_')
            .toLowerCase()}`,
        }),
      )
      .filter(
        (category) => !(category.name === 'trash' && category.count === 0),
      );

    // groups
    let groups = (
      <Categories
        categories={categories}
        active={this.state.group}
        onSelect={this.handleGroup}
      />
    );
    if (propsGroups === false) {
      groups = false;
    }

    // messages
    let messages = {};
    if (propsMessages !== undefined) {
      messages = propsMessages;
    }
    let extraActions;
    if (typeof renderExtraActions === 'function') {
      extraActions = renderExtraActions(this.state);
    }

    const listingClassName = classnames('mailpoet-listing', className);

    return (
      <>
        {this.state.meta.mta_method && (
          <MailerError
            mtaMethod={this.state.meta.mta_method}
            mtaLog={this.state.meta.mta_log}
          />
        )}
        <div className={listingClassName}>
          <div className="mailpoet-listing-header">
            {groups}
            <div>
              {search}
              <ListingFilters
                filters={this.state.filters}
                filter={this.state.filter}
                group={this.state.group}
                onBeforeSelectFilter={onBeforeSelectFilter || null}
                onSelectFilter={this.handleFilter}
                onEmptyTrash={this.handleEmptyTrash}
              />
              {extraActions}
              <ListingPages
                position="top"
                count={this.state.count}
                page={this.state.page}
                limit={this.state.limit}
                onSetPage={this.handleSetPage}
              />
            </div>
          </div>
          <table className={tableClasses}>
            <thead>
              <ListingHeader
                onSort={this.handleSort}
                onSelectItems={this.handleSelectItems}
                selection={this.state.selection}
                sort_by={sortBy}
                sort_order={sortOrder}
                columns={columns}
                is_selectable={bulkActions.length > 0}
              />
            </thead>

            <ListingItems
              onRenderItem={this.handleRenderItem}
              getListingItemKey={getListingItemKey}
              onDeleteItem={this.handleDeleteItem}
              onRestoreItem={this.handleRestoreItem}
              onTrashItem={this.handleTrashItem}
              onRefreshItems={this.handleRefreshItems}
              isItemInactive={isItemInactive}
              columns={columns}
              is_selectable={bulkActions.length > 0}
              onSelectItem={this.handleSelectItem}
              onSelectAll={this.handleSelectAll}
              selection={this.state.selection}
              selected_ids={this.state.selected_ids}
              loading={this.state.loading}
              group={this.state.group}
              count={this.state.count}
              limit={this.state.limit}
              bulk_actions={bulkActions}
              onBulkAction={this.handleBulkAction}
              item_actions={itemActions}
              messages={messages}
              items={items}
              search={this.state.search}
              location={location}
              isItemDeletable={isItemDeletable}
              isItemToggleable={isItemToggleable}
            />
          </table>
          <div className="mailpoet-listing-footer clearfix">
            <ListingPages
              position="bottom"
              count={this.state.count}
              page={this.state.page}
              limit={this.state.limit}
              onSetPage={this.handleSetPage}
            />
          </div>
        </div>
      </>
    );
  }
}

ListingComponent.contextType = GlobalContext;

ListingComponent.propTypes = {
  limit: PropTypes.number,
  sort_by: PropTypes.string,
  sort_order: PropTypes.string,
  params: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  auto_refresh: PropTypes.bool,
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }),
  base_url: PropTypes.string,
  type: PropTypes.string,
  endpoint: PropTypes.string.isRequired,
  afterGetItems: PropTypes.func,
  messages: PropTypes.shape({
    onRestore: PropTypes.func,
    onTrash: PropTypes.func,
    onDelete: PropTypes.func,
  }),
  onRenderItem: PropTypes.func.isRequired,
  isItemInactive: PropTypes.func,
  columns: PropTypes.arrayOf(PropTypes.object), // eslint-disable-line react/forbid-prop-types
  bulk_actions: PropTypes.arrayOf(PropTypes.object), // eslint-disable-line react/forbid-prop-types
  item_actions: PropTypes.arrayOf(PropTypes.object), // eslint-disable-line react/forbid-prop-types
  search: PropTypes.bool,
  groups: PropTypes.bool,
  renderExtraActions: PropTypes.func,
  onBeforeSelectFilter: PropTypes.func,
  getListingItemKey: PropTypes.func,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
  isItemDeletable: PropTypes.func,
  isItemToggleable: PropTypes.func,
  className: PropTypes.string,
};

ListingComponent.displayName = 'Listing';

export const Listing = withRouter(withBoundary(ListingComponent));
