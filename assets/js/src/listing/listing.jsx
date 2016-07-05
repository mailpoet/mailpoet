import MailPoet from 'mailpoet'
import jQuery from 'jquery'
import React from 'react'
import { Router, Link } from 'react-router'
import classNames from 'classnames'
import ListingBulkActions from 'listing/bulk_actions.jsx'
import ListingHeader from 'listing/header.jsx'
import ListingPages from 'listing/pages.jsx'
import ListingSearch from 'listing/search.jsx'
import ListingGroups from 'listing/groups.jsx'
import ListingFilters from 'listing/filters.jsx'

const ListingItem = React.createClass({
  getInitialState: function() {
    return {
      toggled: true
    };
  },
  handleSelectItem: function(e) {
    this.props.onSelectItem(
      parseInt(e.target.value, 10),
      e.target.checked
    );

    return !e.target.checked;
  },
  handleRestoreItem: function(id) {
    this.props.onRestoreItem(id);
  },
  handleTrashItem: function(id) {
    this.props.onTrashItem(id);
  },
  handleDeleteItem: function(id) {
    this.props.onDeleteItem(id);
  },
  handleToggleItem: function(id) {
    this.setState({ toggled: !this.state.toggled });
  },
  render: function() {
    var checkbox = false;

    if (this.props.is_selectable === true) {
      checkbox = (
        <th className="check-column" scope="row">
          <label className="screen-reader-text">{
            'Select ' + this.props.item[this.props.columns[0].name]
          }</label>
          <input
            type="checkbox"
            value={ this.props.item.id }
            checked={
              this.props.item.selected || this.props.selection === 'all'
            }
            onChange={ this.handleSelectItem }
            disabled={ this.props.selection === 'all' } />
        </th>
      );
    }

    const custom_actions = this.props.item_actions;
    let item_actions = false;

    if (custom_actions.length > 0) {
      let is_first = true;
      item_actions = custom_actions.map(function(action, index) {
        if (action.display !== undefined) {
          if (action.display(this.props.item) === false) {
            return;
          }
        }

        let custom_action = null;

        if (action.name === 'trash') {
          custom_action = (
            <span key={ 'action-'+index } className="trash">
              {(!is_first) ? ' | ' : ''}
              <a
                href="javascript:;"
                onClick={ this.handleTrashItem.bind(
                  null,
                  this.props.item.id
                ) }>
                {MailPoet.I18n.t('trash')}
              </a>
            </span>
          );
        } else if (action.refresh) {
          custom_action = (
            <span
              onClick={ this.props.onRefreshItems }
              key={ 'action-'+index } className={ action.name }>
              {(!is_first) ? ' | ' : ''}
              { action.link(this.props.item) }
            </span>
          );
        } else if (action.link) {
          custom_action = (
            <span
              key={ 'action-'+index } className={ action.name }>
              {(!is_first) ? ' | ' : ''}
              { action.link(this.props.item) }
            </span>
          );
        } else {
          custom_action = (
            <span
              key={ 'action-'+index } className={ action.name }>
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
      }.bind(this));
    } else {
      item_actions = (
        <span className="edit">
          <Link to={ `/edit/${ this.props.item.id }` }>{MailPoet.I18n.t('edit')}</Link>
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
                onClick={ this.handleRestoreItem.bind(
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
                onClick={ this.handleDeleteItem.bind(
                  null,
                  this.props.item.id
                )}
              >{MailPoet.I18n.t('deletePermanently')}</a>
            </span>
          </div>
          <button
            onClick={ this.handleToggleItem.bind(null, this.props.item.id) }
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
            onClick={ this.handleToggleItem.bind(null, this.props.item.id) }
            className="toggle-row" type="button">
            <span className="screen-reader-text">{MailPoet.I18n.t('showMoreDetails')}</span>
          </button>
        </div>
      );
    }

    const row_classes = classNames({ 'is-expanded': !this.state.toggled });

    return (
      <tr className={ row_classes }>
        { checkbox }
        { this.props.onRenderItem(this.props.item, actions) }
      </tr>
    );
  }
});


const ListingItems = React.createClass({
  render: function() {
    if (this.props.items.length === 0) {
      return (
        <tbody>
          <tr className="no-items">
            <td
              colSpan={
                this.props.columns.length
                + (this.props.is_selectable ? 1 : 0)
              }
              className="colspanchange">
              {
                (this.props.loading === true)
                ? MailPoet.I18n.t('loadingItems')
                : MailPoet.I18n.t('noItemsFound')
              }
            </td>
          </tr>
        </tbody>
      );
    } else {
      const select_all_classes = classNames(
        'mailpoet_select_all',
        { 'mailpoet_hidden': (
            this.props.selection === false
            || (this.props.count <= this.props.limit)
          )
        }
      );

      return (
        <tbody>
          <tr className={ select_all_classes }>
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
                onClick={ this.props.onSelectAll }
                href="javascript:;">{
                  (this.props.selection !== 'all')
                  ? MailPoet.I18n.t('selectAllLink')
                  : MailPoet.I18n.t('clearSelection')
                }</a>
            </td>
          </tr>

          {this.props.items.map(function(item, index) {
            item.id = parseInt(item.id, 10);
            item.selected = (this.props.selected_ids.indexOf(item.id) !== -1);

            return (
              <ListingItem
                columns={ this.props.columns }
                onSelectItem={ this.props.onSelectItem }
                onRenderItem={ this.props.onRenderItem }
                onDeleteItem={ this.props.onDeleteItem }
                onRestoreItem={ this.props.onRestoreItem }
                onTrashItem={ this.props.onTrashItem }
                onRefreshItems={ this.props.onRefreshItems }
                selection={ this.props.selection }
                is_selectable={ this.props.is_selectable }
                item_actions={ this.props.item_actions }
                group={ this.props.group }
                key={ `item-${item.id}-${index}` }
                item={ item } />
            );
          }.bind(this))}
        </tbody>
      );
    }
  }
});

const Listing = React.createClass({
  contextTypes: {
    router: React.PropTypes.object.isRequired
  },
  getInitialState: function() {
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
      selection: false
    };
  },
  getParam: function(param) {
    const regex = /(.*)\[(.*)\]/;
    const matches = regex.exec(param);
    return [matches[1], matches[2]]
  },
  initWithParams: function(params) {
    let state = this.getInitialState();

     // check for url params
    if (params.splat !== undefined) {
      params.splat.split('/').map(param => {
        let [key, value] = this.getParam(param);
        switch(key) {
          case 'filter':
            let filters = {};
            value.split('&').map(function(pair) {
                let [k, v] = pair.split('=')
                filters[k] = v
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

    this.setState(state, function() {
      this.getItems();
    }.bind(this));
  },
  setParams: function() {
    if (this.props.location) {
      let params = Object.keys(this.state)
        .filter(key => {
          return (
            [
              'group',
              'filter',
              'search',
              'page',
              'sort_by',
              'sort_order'
            ].indexOf(key) !== -1
          )
        })
        .map(key => {
          let value = this.state[key];
          if (value === Object(value)) {
            value = jQuery.param(value)
          } else if (value === Boolean(value)) {
            value = value.toString()
          }

          if (value !== '' && value !== null) {
            return `${key}[${value}]`
          }
        })
        .filter(key => { return (key !== undefined) })
        .join('/');

      // prepend url with "tab" if specified
      if (this.props.tab !== undefined) {
        params = `/${ this.props.tab }/${ params }`;
      } else {
        params = `/${ params }`;
      }

      if (this.props.location.pathname !== params) {
        this.context.router.push(`${params}`);
      }
    }
  },
  componentDidMount: function() {
    if (this.isMounted()) {
      const params = this.props.params || {};
      this.initWithParams(params);

      if (this.props.auto_refresh) {
        jQuery(document).on('heartbeat-tick.mailpoet', function(e, data) {
          this.getItems();
        }.bind(this));
      }
    }
  },
  componentWillReceiveProps: function(nextProps) {
    const params = nextProps.params || {};
    this.initWithParams(params);
  },
  getItems: function() {
    if (this.isMounted()) {
      this.setState({ loading: true });

      this.clearSelection();

      MailPoet.Ajax.post({
        endpoint: this.props.endpoint,
        action: 'listing',
        data: {
          tab: (this.props.tab) ? this.props.tab : '',
          offset: (this.state.page - 1) * this.state.limit,
          limit: this.state.limit,
          group: this.state.group,
          filter: this.state.filter,
          search: this.state.search,
          sort_by: this.state.sort_by,
          sort_order: this.state.sort_order
        }
      }).done(function(response) {
        this.setState({
          items: response.items || [],
          filters: response.filters || {},
          groups: response.groups || [],
          count: response.count || 0,
          loading: false
        }, function() {
          if (this.props['onGetItems'] !== undefined) {
            const count = (response.groups[0] !== undefined)
              ? ~~(response.groups[0].count)
              : 0;
            this.props.onGetItems(count);
          }
        }.bind(this));
      }.bind(this));
    }
  },
  handleRestoreItem: function(id) {
    this.setState({
      loading: true,
      page: 1
    });

    MailPoet.Ajax.post({
      endpoint: this.props.endpoint,
      action: 'restore',
      data: id
    }).done(function(response) {
      if (
        this.props.messages !== undefined
        && this.props.messages['onRestore'] !== undefined
      ) {
        this.props.messages.onRestore(response);
      }
      this.getItems();
    }.bind(this));
  },
  handleTrashItem: function(id) {
    this.setState({
      loading: true,
      page: 1
    });

    MailPoet.Ajax.post({
      endpoint: this.props.endpoint,
      action: 'trash',
      data: id
    }).done(function(response) {
      if (
        this.props.messages !== undefined
        && this.props.messages['onTrash'] !== undefined
      ) {
        this.props.messages.onTrash(response);
      }
      this.getItems();
    }.bind(this));
  },
  handleDeleteItem: function(id) {
    this.setState({
      loading: true,
      page: 1
    });

    MailPoet.Ajax.post({
      endpoint: this.props.endpoint,
      action: 'delete',
      data: id
    }).done(function(response) {
      if (
        this.props.messages !== undefined
        && this.props.messages['onDelete'] !== undefined
      ) {
        this.props.messages.onDelete(response);
      }
      this.getItems();
    }.bind(this));
  },
  handleEmptyTrash: function() {
    return this.handleBulkAction('all', {
      action: 'delete',
      group: 'trash'
    }).then(function(response) {
      if (~~(response) > 0) {
        MailPoet.Notice.success(
          MailPoet.I18n.t('permanentlyDeleted').replace('%d', response)
        );
      }

      // redirect to default group
      this.handleGroup('all');
    }.bind(this));
  },
  handleBulkAction: function(selected_ids, params) {
    if (
      this.state.selection === false
      && this.state.selected_ids.length === 0
      && selected_ids !== 'all'
    ) {
      return;
    }

    this.setState({ loading: true });

    var data = params || {};
    data.listing = {
      tab: (this.props.tab) ? this.props.tab : '',
      offset: 0,
      limit: 0,
      filter: this.state.filter,
      group: this.state.group,
      search: this.state.search
    }
    if (selected_ids !== 'all') {
      data.listing.selection = selected_ids;
    }

    return MailPoet.Ajax.post({
      endpoint: this.props.endpoint,
      action: 'bulkAction',
      data: data
    }).done(() => {
      this.getItems();
    });
  },
  handleSearch: function(search) {
    this.setState({
      search: search,
      page: 1,
      selection: false,
      selected_ids: []
    }, function() {
      this.setParams();
    }.bind(this));
  },
  handleSort: function(sort_by, sort_order = 'asc') {
    this.setState({
      sort_by: sort_by,
      sort_order: sort_order,
    }, function() {
      this.setParams();
    }.bind(this));
  },
  handleSelectItem: function(id, is_checked) {
    var selected_ids = this.state.selected_ids,
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
      selected_ids: selected_ids
    });
  },
  handleSelectItems: function(is_checked) {
    if (is_checked === false) {
      this.clearSelection();
    } else {
      var selected_ids = this.state.items.map(function(item) {
        return ~~item.id;
      });

      this.setState({
        selected_ids: selected_ids,
        selection: 'page'
      });
    }
  },
  handleSelectAll: function() {
    if (this.state.selection === 'all') {
      this.clearSelection();
    } else {
      this.setState({
        selection: 'all',
        selected_ids: []
      });
    }
  },
  clearSelection: function() {
    this.setState({
      selection: false,
      selected_ids: []
    });
  },
  handleFilter: function(filters) {
    this.setState({
      filter: filters,
      page: 1
    }, function() {
      this.setParams();
    }.bind(this));
  },
  handleGroup: function(group) {
    // reset search
    jQuery('#search_input').val('');

    this.setState({
      group: group,
      filter: {},
      search: '',
      page: 1
    }, function() {
      this.setParams();
    }.bind(this));
  },
  handleSetPage: function(page) {
    this.setState({
      page: page,
      selection: false,
      selected_ids: []
    }, function() {
      this.setParams();
    }.bind(this));
  },
  handleRenderItem: function(item, actions) {
    const render = this.props.onRenderItem(item, actions);
    return render.props.children;
  },
  handleRefreshItems: function() {
    this.getItems();
  },
  render: function() {
    const items = this.state.items;
    const sort_by = this.state.sort_by;
    const sort_order = this.state.sort_order;

    // columns
    let columns = this.props.columns || [];
    columns = columns.filter(function(column) {
      return (column.display === undefined || !!(column.display) === true);
    });

    // bulk actions
    let bulk_actions = this.props.bulk_actions || [];

    if (this.state.group === 'trash' && bulk_actions.length > 0) {
      bulk_actions = [
        {
          name: 'restore',
          label: MailPoet.I18n.t('restore'),
          onSuccess: this.props.messages.onRestore
        },
        {
          name: 'delete',
          label: MailPoet.I18n.t('deletePermanently'),
          onSuccess: this.props.messages.onDelete
        }
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
      { 'mailpoet_listing_loading': this.state.loading }
    );

    // search
    let search = (
      <ListingSearch
        onSearch={ this.handleSearch }
        search={ this.state.search }
      />
    );
    if (this.props.search === false) {
      search = false;
    }

    // groups
    let groups = (
      <ListingGroups
        groups={ this.state.groups }
        group={ this.state.group }
        onSelectGroup={ this.handleGroup }
      />
    );
    if (this.props.groups === false) {
      groups = false;
    }

    return (
      <div>
        { groups }
        { search }
        <div className="tablenav top clearfix">
          <ListingBulkActions
            count={ this.state.count }
            bulk_actions={ bulk_actions }
            selection={ this.state.selection }
            selected_ids={ this.state.selected_ids }
            onBulkAction={ this.handleBulkAction } />
          <ListingFilters
            filters={ this.state.filters }
            filter={ this.state.filter }
            group={ this.state.group }
            onSelectFilter={ this.handleFilter }
            onEmptyTrash={ this.handleEmptyTrash }
          />
          <ListingPages
            count={ this.state.count }
            page={ this.state.page }
            limit={ this.state.limit }
            onSetPage={ this.handleSetPage } />
        </div>
        <table className={ table_classes }>
          <thead>
            <ListingHeader
              onSort={ this.handleSort }
              onSelectItems={ this.handleSelectItems }
              selection={ this.state.selection }
              sort_by={ sort_by }
              sort_order={ sort_order }
              columns={ columns }
              is_selectable={ bulk_actions.length > 0 } />
          </thead>

          <ListingItems
            onRenderItem={ this.handleRenderItem }
            onDeleteItem={ this.handleDeleteItem }
            onRestoreItem={ this.handleRestoreItem }
            onTrashItem={ this.handleTrashItem }
            onRefreshItems={ this.handleRefreshItems }
            columns={ columns }
            is_selectable={ bulk_actions.length > 0 }
            onSelectItem={ this.handleSelectItem }
            onSelectAll={ this.handleSelectAll }
            selection={ this.state.selection }
            selected_ids={ this.state.selected_ids }
            loading={ this.state.loading }
            group={ this.state.group }
            count={ this.state.count }
            limit={ this.state.limit }
            item_actions={ item_actions }
            items={ items } />

          <tfoot>
            <ListingHeader
              onSort={ this.handleSort }
              onSelectItems={ this.handleSelectItems }
              selection={ this.state.selection }
              sort_by={ sort_by }
              sort_order={ sort_order }
              columns={ columns }
              is_selectable={ bulk_actions.length > 0 } />
          </tfoot>

        </table>
        <div className="tablenav bottom">
          <ListingBulkActions
            count={ this.state.count }
            bulk_actions={ bulk_actions }
            selection={ this.state.selection }
            selected_ids={ this.state.selected_ids }
            onBulkAction={ this.handleBulkAction } />
          <ListingPages
            count={ this.state.count }
            page={ this.state.page }
            limit={ this.state.limit }
            onSetPage={ this.handleSetPage } />
        </div>
      </div>
    );
  }
});

module.exports = Listing;