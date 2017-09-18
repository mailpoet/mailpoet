import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import React from 'react';
import _ from 'underscore';
import { Link } from 'react-router';
import classNames from 'classnames';
import ListingBulkActions from 'listing/bulk_actions.jsx';
import ListingHeader from 'listing/header.jsx';
import ListingPages from 'listing/pages.jsx';
import ListingSearch from 'listing/search.jsx';
import ListingGroups from 'listing/groups.jsx';
import ListingFilters from 'listing/filters.jsx';

const ListingItem = React.createClass({
  getInitialState: function () {
    return {
      expanded: false,
    };
  },
  handleSelectItem: function (e) {
    this.props.onSelectItem(
      parseInt(e.target.value, 10),
      e.target.checked
    );

    return !e.target.checked;
  },
  handleRestoreItem: function (id) {
    this.props.onRestoreItem(id);
  },
  handleTrashItem: function (id) {
    this.props.onTrashItem(id);
  },
  handleDeleteItem: function (id) {
    this.props.onDeleteItem(id);
  },
  handleToggleItem: function () {
    this.setState({ expanded: !this.state.expanded });
  },
  render: function () {
    let checkbox = false;

    if (this.props.is_selectable === true) {
      checkbox = (
        <th className="check-column" scope="row">
          <label className="screen-reader-text">{
            'Select ' + this.props.item[this.props.columns[0].name]
          }</label>
          <input
            type="checkbox"
            value={this.props.item.id}
            checked={
              this.props.item.selected || this.props.selection === 'all'
            }
            onChange={this.handleSelectItem}
            disabled={this.props.selection === 'all'} />
        </th>
      );
    }

    const custom_actions = this.props.item_actions;
    let item_actions = false;

    if (custom_actions.length > 0) {
      let is_first = true;
      item_actions = custom_actions.map((action, index) => {
        if (action.display !== undefined) {
          if (action.display(this.props.item) === false) {
            return;
          }
        }

        let custom_action = null;

        if (action.name === 'trash') {
          custom_action = (
            <span key={'action-'+index} className="trash">
              {(!is_first) ? ' | ' : ''}
              <a
                href="javascript:;"
                onClick={this.handleTrashItem.bind(
                  null,
                  this.props.item.id
                )}>
                {MailPoet.I18n.t('moveToTrash')}
              </a>
            </span>
          );
        } else if (action.refresh) {
          custom_action = (
            <span
              onClick={this.props.onRefreshItems}
              key={'action-'+index} className={action.name}>
              {(!is_first) ? ' | ' : ''}
              { action.link(this.props.item) }
            </span>
          );
        } else if (action.link) {
          custom_action = (
            <span
              key={'action-'+index} className={action.name}>
              {(!is_first) ? ' | ' : ''}
              { action.link(this.props.item) }
            </span>
          );
        } else {
          custom_action = (
            <span
              key={'action-'+index} className={action.name}>
              {(!is_first) ? ' | ' : ''}
              <a href="javascript:;" onClick={
                (action.onClick !== undefined)
                ? action.onClick.bind(null,
                    this.props.item,
                    this.props.onRefreshItems
                  )
                : false
              }>{ action.label }</a>
            </span>
          );
        }

        if (custom_action !== null && is_first === true) {
          is_first = false;
        }

        return custom_action;
      });
    } else {
      item_actions = (
        <span className="edit">
          <Link to={`/edit/${ this.props.item.id }`}>{MailPoet.I18n.t('edit')}</Link>
        </span>
      );
    }

    let actions;

    if (this.props.group === 'trash') {
      actions = (
        <div>
          <div className="row-actions">
            <span>
              <a
                href="javascript:;"
                onClick={this.handleRestoreItem.bind(
                  null,
                  this.props.item.id
                )}
              >{MailPoet.I18n.t('restore')}</a>
            </span>
            { ' | ' }
            <span className="delete">
              <a
                className="submitdelete"
                href="javascript:;"
                onClick={this.handleDeleteItem.bind(
                  null,
                  this.props.item.id
                )}
              >{MailPoet.I18n.t('deletePermanently')}</a>
            </span>
          </div>
          <button
            onClick={this.handleToggleItem.bind(null, this.props.item.id)}
            className="toggle-row" type="button">
            <span className="screen-reader-text">{MailPoet.I18n.t('showMoreDetails')}</span>
          </button>
        </div>
      );
    } else {
      actions = (
        <div>
          <div className="row-actions">
            { item_actions }
          </div>
          <button
            onClick={this.handleToggleItem.bind(null, this.props.item.id)}
            className="toggle-row" type="button">
            <span className="screen-reader-text">{MailPoet.I18n.t('showMoreDetails')}</span>
          </button>
        </div>
      );
    }

    const row_classes = classNames({ 'is-expanded': this.state.expanded });

    return (
      <tr className={row_classes}>
        { checkbox }
        { this.props.onRenderItem(this.props.item, actions) }
      </tr>
    );
  },
});


const ListingItems = React.createClass({
  render: function () {
    if (this.props.items.length === 0) {
      let message;
      if (this.props.loading === true) {
        message = (this.props.messages.onLoadingItems
          && this.props.messages.onLoadingItems(this.props.group))
          || MailPoet.I18n.t('loadingItems');
      } else {
        message = (this.props.messages.onNoItemsFound
          && this.props.messages.onNoItemsFound(this.props.group))
          || MailPoet.I18n.t('noItemsFound');
      }

      return (
        <tbody>
          <tr className="no-items">
            <td
              colSpan={
                this.props.columns.length
                + (this.props.is_selectable ? 1 : 0)
              }
              className="colspanchange">
              {message}
            </td>
          </tr>
        </tbody>
      );
    } else {
      const select_all_classes = classNames(
        'mailpoet_select_all',
        { mailpoet_hidden: (
            this.props.selection === false
            || (this.props.count <= this.props.limit)
          ),
        }
      );

      return (
        <tbody>
          <tr className={select_all_classes}>
            <td colSpan={
                this.props.columns.length
                + (this.props.is_selectable ? 1 : 0)
              }>
              {
                (this.props.selection !== 'all')
                ? MailPoet.I18n.t('selectAllLabel')
                : MailPoet.I18n.t('selectedAllLabel').replace(
                  '%d',
                  this.props.count
                )
              }
              &nbsp;
              <a
                onClick={this.props.onSelectAll}
                href="javascript:;">{
                  (this.props.selection !== 'all')
                  ? MailPoet.I18n.t('selectAllLink')
                  : MailPoet.I18n.t('clearSelection')
                }</a>
            </td>
          </tr>

          {this.props.items.map((item, index) => {
            const renderItem = item;
            renderItem.id = parseInt(item.id, 10);
            renderItem.selected = (this.props.selected_ids.indexOf(renderItem.id) !== -1);

            return (
              <ListingItem
                columns={this.props.columns}
                onSelectItem={this.props.onSelectItem}
                onRenderItem={this.props.onRenderItem}
                onDeleteItem={this.props.onDeleteItem}
                onRestoreItem={this.props.onRestoreItem}
                onTrashItem={this.props.onTrashItem}
                onRefreshItems={this.props.onRefreshItems}
                selection={this.props.selection}
                is_selectable={this.props.is_selectable}
                item_actions={this.props.item_actions}
                group={this.props.group}
                key={`item-${renderItem.id}-${index}`}
                item={renderItem} />
            );
          })}
        </tbody>
      );
    }
  },
});

const Listing = React.createClass({
  contextTypes: {
    router: React.PropTypes.object.isRequired,
  },
  getInitialState: function () {
    return {
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
    };
  },
  getParam: function (param) {
    const regex = /(.*)\[(.*)\]/;
    const matches = regex.exec(param);
    return [matches[1], matches[2]];
  },
  initWithParams: function (params) {
    const state = this.getInitialState();
     // check for url params
    if (params.splat) {
      params.splat.split('/').map((param) => {
        const [key, value] = this.getParam(param);
        switch(key) {
          case 'filter':
            const filters = {};
            value.split('&').map((pair) => {
              const [k, v] = pair.split('=');
              filters[k] = v;
            }
            );

            state.filter = filters;
            break;
          default:
            state[key] = value;
        }
      });
    }

    // limit per page
    if (this.props.limit !== undefined) {
      state.limit = Math.abs(~~this.props.limit);
    }

    // sort by
    if (state.sort_by === null && this.props.sort_by !== undefined) {
      state.sort_by = this.props.sort_by;
    }

    // sort order
    if (state.sort_order === null && this.props.sort_order !== undefined) {
      state.sort_order = this.props.sort_order;
    }

    this.setState(state, () => {
      this.getItems();
    });
  },
  getParams: function () {
    // get all route parameters (without the "splat")
    const params = _.omit(this.props.params, 'splat');
    // TODO:
    // find a way to set the "type" in the routes definition
    // so that it appears in `this.props.params`
    if (this.props.type) {
      params.type = this.props.type;
    }
    return params;
  },
  setParams: function () {
    if (this.props.location) {
      const params = Object.keys(this.state)
        .filter((key) => {
          return (
            [
              'group',
              'filter',
              'search',
              'page',
              'sort_by',
              'sort_order',
            ].indexOf(key) !== -1
          );
        })
        .map((key) => {
          let value = this.state[key];
          if (value === Object(value)) {
            value = jQuery.param(value);
          } else if (value === Boolean(value)) {
            value = value.toString();
          }

          if (value !== '' && value !== null) {
            return `${key}[${value}]`;
          }
        })
        .filter((key) => { return (key !== undefined); })
        .join('/');

      // set url
      const url = this.getUrlWithParams(params);

      if (this.props.location.pathname !== url) {
        this.context.router.push(`${url}`);
      }
    }
  },
  getUrlWithParams: function (params) {
    let base_url = (this.props.base_url !== undefined)
      ? this.props.base_url
      : null;

    if (base_url !== null) {
      base_url = this.setBaseUrlParams(base_url);
      return `/${ base_url }/${ params }`;
    } else {
      return `/${ params }`;
    }
  },
  setBaseUrlParams: function (base_url) {
    let ret = base_url;
    if (ret.indexOf(':') !== -1) {
      const params = this.getParams();
      Object.keys(params).map((key) => {
        if (ret.indexOf(':'+key) !== -1) {
          ret = ret.replace(':'+key, params[key]);
        }
      });
    }

    return ret;
  },
  componentDidMount: function () {
    if (this.isMounted()) {
      const params = this.props.params || {};
      this.initWithParams(params);

      if (this.props.auto_refresh) {
        jQuery(document).on('heartbeat-tick.mailpoet', () => {
          this.getItems();
        });
      }
    }
  },
  componentWillReceiveProps: function (nextProps) {
    const params = nextProps.params || {};
    this.initWithParams(params);
  },
  getItems: function () {
    if (this.isMounted()) {
      this.setState({ loading: true });

      this.clearSelection();

      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: this.props.endpoint,
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
      }).always(() => {
        this.setState({ loading: false });
      }).done((response) => {
        this.setState({
          items: response.data || [],
          filters: response.meta.filters || {},
          groups: response.meta.groups || [],
          count: response.meta.count || 0,
          meta: _.omit(response.meta, ['filters', 'groups', 'count']),
        }, () => {
          // if viewing an empty trash
          if (this.state.group === 'trash' && response.meta.count === 0) {
            // redirect to default group
            this.handleGroup('all');
          }

          // trigger afterGetItems callback if specified
          if (this.props.afterGetItems !== undefined) {
            this.props.afterGetItems(this.state);
          }
        });
      }).fail((response) => {
        if(response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => { return error.message; }),
            { scroll: true }
          );
        }
      });
    }
  },
  handleRestoreItem: function (id) {
    this.setState({
      loading: true,
      page: 1,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: this.props.endpoint,
      action: 'restore',
      data: {
        id: id,
      },
    }).done((response) => {
      if (
        this.props.messages !== undefined
        && this.props.messages['onRestore'] !== undefined
      ) {
        this.props.messages.onRestore(response);
      }
      this.getItems();
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => { return error.message; }),
        { scroll: true }
      );
    });
  },
  handleTrashItem: function (id) {
    this.setState({
      loading: true,
      page: 1,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: this.props.endpoint,
      action: 'trash',
      data: {
        id: id,
      },
    }).done((response) => {
      if (
        this.props.messages !== undefined
        && this.props.messages['onTrash'] !== undefined
      ) {
        this.props.messages.onTrash(response);
      }
      this.getItems();
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => { return error.message; }),
        { scroll: true }
      );
    });
  },
  handleDeleteItem: function (id) {
    this.setState({
      loading: true,
      page: 1,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: this.props.endpoint,
      action: 'delete',
      data: {
        id: id,
      },
    }).done((response) => {
      if (
        this.props.messages !== undefined
        && this.props.messages['onDelete'] !== undefined
      ) {
        this.props.messages.onDelete(response);
      }
      this.getItems();
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => { return error.message; }),
        { scroll: true }
      );
    });
  },
  handleEmptyTrash: function () {
    return this.handleBulkAction('all', {
      action: 'delete',
      group: 'trash',
    }).done((response) => {
      MailPoet.Notice.success(
        MailPoet.I18n.t('permanentlyDeleted').replace('%d', response.meta.count)
      );
      // redirect to default group
      this.handleGroup('all');
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => { return error.message; }),
        { scroll: true }
      );
    });
  },
  handleBulkAction: function (selected_ids, params) {
    if (
      this.state.selection === false
      && this.state.selected_ids.length === 0
      && selected_ids !== 'all'
    ) {
      return false;
    }

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
    if (selected_ids !== 'all') {
      data.listing.selection = selected_ids;
    }

    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: this.props.endpoint,
      action: 'bulkAction',
      data: data,
    }).done(() => {
      this.getItems();
    }).fail((response) => {
      if(response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => { return error.message; }),
          { scroll: true }
        );
      }
    });
  },
  handleSearch: function (search) {
    this.setState({
      search: search,
      page: 1,
      selection: false,
      selected_ids: [],
    }, () => {
      this.setParams();
    });
  },
  handleSort: function (sort_by, sort_order = 'asc') {
    this.setState({
      sort_by: sort_by,
      sort_order: (sort_order === 'asc') ? 'asc' : 'desc',
    }, () => {
      this.setParams();
    });
  },
  handleSelectItem: function (id, is_checked) {
    let selected_ids = this.state.selected_ids,
      selection = false;

    if (is_checked) {
      selected_ids = jQuery.merge(selected_ids, [ id ]);
      // check whether all items on the page are selected
      if (
        jQuery('tbody .check-column :checkbox:not(:checked)').length === 0
      ) {
        selection = 'page';
      }
    } else {
      selected_ids.splice(selected_ids.indexOf(id), 1);
    }

    this.setState({
      selection: selection,
      selected_ids: selected_ids,
    });
  },
  handleSelectItems: function (is_checked) {
    if (is_checked === false) {
      this.clearSelection();
    } else {
      const selected_ids = this.state.items.map((item) => {
        return ~~item.id;
      });

      this.setState({
        selected_ids: selected_ids,
        selection: 'page',
      });
    }
  },
  handleSelectAll: function () {
    if (this.state.selection === 'all') {
      this.clearSelection();
    } else {
      this.setState({
        selection: 'all',
        selected_ids: [],
      });
    }
  },
  clearSelection: function () {
    this.setState({
      selection: false,
      selected_ids: [],
    });
  },
  handleFilter: function (filters) {
    this.setState({
      filter: filters,
      page: 1,
    }, () => {
      this.setParams();
    });
  },
  handleGroup: function (group) {
    // reset search
    jQuery('#search_input').val('');

    this.setState({
      group: group,
      filter: {},
      search: '',
      page: 1,
    }, () => {
      this.setParams();
    });
  },
  handleSetPage: function (page) {
    this.setState({
      page: page,
      selection: false,
      selected_ids: [],
    }, () => {
      this.setParams();
    });
  },
  handleRenderItem: function (item, actions) {
    const render = this.props.onRenderItem(item, actions, this.state.meta);
    return render.props.children;
  },
  handleRefreshItems: function () {
    this.getItems();
  },
  render: function () {
    const items = this.state.items;
    const sort_by = this.state.sort_by;
    const sort_order = this.state.sort_order;

    // columns
    let columns = this.props.columns || [];
    columns = columns.filter((column) => {
      return (column.display === undefined || !!(column.display) === true);
    });

    // bulk actions
    let bulk_actions = this.props.bulk_actions || [];

    if (this.state.group === 'trash' && bulk_actions.length > 0) {
      bulk_actions = [
        {
          name: 'restore',
          label: MailPoet.I18n.t('restore'),
          onSuccess: this.props.messages.onRestore,
        },
        {
          name: 'delete',
          label: MailPoet.I18n.t('deletePermanently'),
          onSuccess: this.props.messages.onDelete,
        },
      ];
    }

    // item actions
    const item_actions = this.props.item_actions || [];

    const table_classes = classNames(
      'mailpoet_listing_table',
      'wp-list-table',
      'widefat',
      'fixed',
      'striped',
      { mailpoet_listing_loading: this.state.loading }
    );

    // search
    let search = (
      <ListingSearch
        onSearch={this.handleSearch}
        search={this.state.search}
      />
    );
    if (this.props.search === false) {
      search = false;
    }

    // groups
    let groups = (
      <ListingGroups
        groups={this.state.groups}
        group={this.state.group}
        onSelectGroup={this.handleGroup}
      />
    );
    if (this.props.groups === false) {
      groups = false;
    }

    // messages
    let messages = {};
    if (this.props.messages !== undefined) {
      messages = this.props.messages;
    }

    return (
      <div>
        { groups }
        { search }
        <div className="tablenav top clearfix">
          <ListingBulkActions
            count={this.state.count}
            bulk_actions={bulk_actions}
            selection={this.state.selection}
            selected_ids={this.state.selected_ids}
            onBulkAction={this.handleBulkAction} />
          <ListingFilters
            filters={this.state.filters}
            filter={this.state.filter}
            group={this.state.group}
            onBeforeSelectFilter={this.props.onBeforeSelectFilter || null}
            onSelectFilter={this.handleFilter}
            onEmptyTrash={this.handleEmptyTrash}
          />
          <ListingPages
            count={this.state.count}
            page={this.state.page}
            limit={this.state.limit}
            onSetPage={this.handleSetPage} />
        </div>
        <table className={table_classes}>
          <thead>
            <ListingHeader
              onSort={this.handleSort}
              onSelectItems={this.handleSelectItems}
              selection={this.state.selection}
              sort_by={sort_by}
              sort_order={sort_order}
              columns={columns}
              is_selectable={bulk_actions.length > 0} />
          </thead>

          <ListingItems
            onRenderItem={this.handleRenderItem}
            onDeleteItem={this.handleDeleteItem}
            onRestoreItem={this.handleRestoreItem}
            onTrashItem={this.handleTrashItem}
            onRefreshItems={this.handleRefreshItems}
            columns={columns}
            is_selectable={bulk_actions.length > 0}
            onSelectItem={this.handleSelectItem}
            onSelectAll={this.handleSelectAll}
            selection={this.state.selection}
            selected_ids={this.state.selected_ids}
            loading={this.state.loading}
            group={this.state.group}
            count={this.state.count}
            limit={this.state.limit}
            item_actions={item_actions}
            messages={messages}
            items={items} />

          <tfoot>
            <ListingHeader
              onSort={this.handleSort}
              onSelectItems={this.handleSelectItems}
              selection={this.state.selection}
              sort_by={sort_by}
              sort_order={sort_order}
              columns={columns}
              is_selectable={bulk_actions.length > 0} />
          </tfoot>

        </table>
        <div className="tablenav bottom">
          <ListingBulkActions
            count={this.state.count}
            bulk_actions={bulk_actions}
            selection={this.state.selection}
            selected_ids={this.state.selected_ids}
            onBulkAction={this.handleBulkAction} />
          <ListingPages
            count={this.state.count}
            page={this.state.page}
            limit={this.state.limit}
            onSetPage={this.handleSetPage} />
        </div>
      </div>
    );
  },
});

module.exports = Listing;
