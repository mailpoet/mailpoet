define(
  [
    'mailpoet',
    'jquery',
    'react',
    'react-router',
    'classnames',
    'listing/bulk_actions.jsx',
    'listing/header.jsx',
    'listing/pages.jsx',
    'listing/search.jsx',
    'listing/groups.jsx',
    'listing/filters.jsx'
  ],
  function(
    MailPoet,
    jQuery,
    React,
    Router,
    classNames,
    ListingBulkActions,
    ListingHeader,
    ListingPages,
    ListingSearch,
    ListingGroups,
    ListingFilters
  ) {
    var Link = Router.Link;

    var ListingItem = React.createClass({
      getInitialState: function() {
        return {
          toggled: true
        };
      },
      handleSelectItem: function(e) {
        var is_checked = jQuery(e.target).is(':checked');

        this.props.onSelectItem(
          parseInt(e.target.value, 10),
          is_checked
        );

        return !e.target.checked;
      },
      handleRestoreItem: function(id) {
        this.props.onRestoreItem(id);
      },
      handleDeleteItem: function(id, confirm = false) {
        this.props.onDeleteItem(id, confirm);
      },
      handleToggleItem: function(id) {
        this.setState({ toggled: !this.state.toggled });
      },
      render: function() {
        var checkbox = false;

        if(this.props.is_selectable === true) {
          checkbox = (
            <th className="check-column" scope="row">
              <label className="screen-reader-text">
                { 'Select ' + this.props.item.email }</label>
              <input
                type="checkbox"
                defaultValue={ this.props.item.id }
                checked={
                  this.props.item.selected || this.props.selection === 'all'
                }
                onChange={ this.handleSelectItem }
                disabled={ this.props.selection === 'all' } />
            </th>
          );
        }

        var custom_actions = this.props.item_actions;
        var item_actions = false;

        if(custom_actions.length > 0) {
          item_actions = custom_actions.map(function(action, index) {
            if(action.refresh) {
              return (
                <span
                  onClick={ this.props.onRefreshItems }
                  key={ 'action-'+index } className={ action.name }>
                  { action.link(this.props.item) }
                  {(index < (custom_actions.length - 1)) ? ' | ' : ''}
                </span>
              );
            } else {
              return (
                <span key={ 'action-'+index } className={ action.name }>
                  { action.link(this.props.item) }
                  {(index < (custom_actions.length - 1)) ? ' | ' : ''}
                </span>
              );
            }
          }.bind(this));
        } else {
          item_actions = (
            <span className="edit">
              <Link to={ `/edit/${ this.props.item.id }` }>Edit</Link>
            </span>
          );
        }

        if(this.props.group === 'trash') {
           var actions = (
            <div>
              <div className="row-actions">
                <span>
                  <a
                    href="javascript:;"
                    onClick={ this.handleRestoreItem.bind(
                      null,
                      this.props.item.id
                    )}
                  >Restore</a>
                </span>
                { ' | ' }
                <span className="delete">
                  <a
                    className="submitdelete"
                    href="javascript:;"
                    onClick={ this.handleDeleteItem.bind(
                      null,
                      this.props.item.id,
                      true
                    )}
                  >Delete permanently</a>
                </span>
              </div>
              <button
                onClick={ this.handleToggleItem.bind(null, this.props.item.id) }
                className="toggle-row" type="button">
                <span className="screen-reader-text">Show more details</span>
              </button>
            </div>
          );
        } else {
          var actions = (
            <div>
              <div className="row-actions">
                { item_actions }
                { ' | ' }
                <span className="trash">
                  <a
                    href="javascript:;"
                    onClick={ this.handleDeleteItem.bind(
                      null,
                      this.props.item.id,
                      false
                    ) }>
                    Trash
                  </a>
                </span>
              </div>
              <button
                onClick={ this.handleToggleItem.bind(null, this.props.item.id) }
                className="toggle-row" type="button">
                <span className="screen-reader-text">Show more details</span>
              </button>
            </div>
          );
        }

        var row_classes = classNames({ 'is-expanded': !this.state.toggled })

        return (
          <tr className={ row_classes }>
            { checkbox }
            { this.props.onRenderItem(this.props.item, actions) }
          </tr>
        );
      }
    });


    var ListingItems = React.createClass({
      render: function() {
        if(this.props.items.length === 0) {
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
                    ? MailPoetI18n.loadingItems
                    : MailPoetI18n.noItemsFound
                  }
                </td>
              </tr>
            </tbody>
          );
        } else {

          var selectAllClasses = classNames(
            'mailpoet_select_all',
            { 'mailpoet_hidden': (
                this.props.selection === false
                || (this.props.count <= this.props.limit)
              )
            }
          );

          return (
            <tbody>
              <tr className={ selectAllClasses }>
                <td colSpan={
                    this.props.columns.length
                    + (this.props.is_selectable ? 1 : 0)
                  }>
                  {
                    (this.props.selection !== 'all')
                    ? MailPoetI18n.selectAllLabel
                    : MailPoetI18n.selectedAllLabel.replace(
                      '%d',
                      this.props.count
                    )
                  }
                  &nbsp;
                  <a
                    onClick={ this.props.onSelectAll }
                    href="javascript:;">{
                      (this.props.selection !== 'all')
                      ? MailPoetI18n.selectAllLink
                      : MailPoetI18n.clearSelection
                    }</a>
                </td>
              </tr>

              {this.props.items.map(function(item) {
                item.id = parseInt(item.id, 10);
                item.selected = (this.props.selected_ids.indexOf(item.id) !== -1);

                return (
                  <ListingItem
                    columns={ this.props.columns }
                    onSelectItem={ this.props.onSelectItem }
                    onRenderItem={ this.props.onRenderItem }
                    onDeleteItem={ this.props.onDeleteItem }
                    onRestoreItem={ this.props.onRestoreItem }
                    onRefreshItems={ this.props.onRefreshItems }
                    selection={ this.props.selection }
                    is_selectable={ this.props.is_selectable }
                    item_actions={ this.props.item_actions }
                    group={ this.props.group }
                    key={ 'item-' + item.id }
                    item={ item } />
                );
              }.bind(this))}
            </tbody>
          );
        }
      }
    });

    var Listing = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          loading: false,
          search: '',
          page: 1,
          count: 0,
          limit: 10,
          sort_by: 'id',
          sort_order: 'desc',
          items: [],
          groups: [],
          group: 'all',
          filters: {},
          filter: {},
          selected_ids: [],
          selection: false
        };
      },
      componentDidUpdate: function(prevProps, prevState) {
        // set group to "all" if trash gets emptied
        if(
          (prevState.group === 'trash' && prevState.count > 0)
          &&
          (this.state.group === 'trash' && this.state.count === 0)
        ) {
          this.handleGroup('all');
        }
      },
      componentDidMount: function() {
        if(this.isMounted()) {
          var state = this.state || {};
          var params = this.props.params || {};

          // set filters
          if(params.filter !== undefined) {
            var filter = {};
            var pairs = params.filter
              .split('&')
              .map(function(pair) {
                var [key, value] = pair.split('=');
                filter[key] = value;
              }
            );

            state.filter = filter;
          }

          if(this.props.limit !== undefined) {
            state.limit = Math.abs(~~this.props.limit);
          }

          this.setState(state, function() {
            this.getItems();
          }.bind(this));
        }
      },
      getItems: function() {
        if(this.isMounted()) {
          this.setState({ loading: true });

          this.clearSelection();

          MailPoet.Ajax.post({
            endpoint: this.props.endpoint,
            action: 'listing',
            data: {
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
            });
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
          if(
            this.props.messages !== undefined
            && this.props.messages['onRestore'] !== undefined
          ) {
            this.props.messages.onRestore(response);
          }
          this.getItems();
        }.bind(this));
      },
      handleDeleteItem: function(id, confirm = false) {
        this.setState({
          loading: true,
          page: 1
        });

        MailPoet.Ajax.post({
          endpoint: this.props.endpoint,
          action: 'delete',
          data: {
            id: id,
            confirm: confirm
          }
        }).done(function(response) {
          if(confirm === true) {
            if(
              this.props.messages !== undefined
              && this.props.messages['onConfirmDelete'] !== undefined
            ) {
              this.props.messages.onConfirmDelete(response);
            }
          } else {
            if(
              this.props.messages !== undefined
              && this.props.messages['onDelete'] !== undefined
            ) {
              this.props.messages.onDelete(response);
            }
          }

          this.getItems();
        }.bind(this));
      },
      handleBulkAction: function(selected_ids, params) {
        if(
          this.state.selection === false
          && this.state.selected_ids.length === 0
        ) {
          return;
        }

        this.setState({ loading: true });

        var data = params || {};
        var callback = ((data['onSuccess'] !== undefined)
          ? data['onSuccess']
          : function() {}
        );
        delete data.onSuccess;

        data.listing = {
          offset: 0,
          limit: 0,
          filter: this.state.filter,
          group: this.state.group,
          search: this.state.search,
          selection: selected_ids
        }

        MailPoet.Ajax.post({
          endpoint: this.props.endpoint,
          action: 'bulk_action',
          data: data
        }).done(function(response) {
          this.getItems();
          callback(response);
        }.bind(this));
      },
      handleSearch: function(search) {
        this.setState({
          search: search,
          page: 1,
          selection: false,
          selected_ids: []
        }, function() {
          this.getItems();
        }.bind(this));
      },
      handleSort: function(sort_by, sort_order = 'asc') {
        this.setState({
          sort_by: sort_by,
          sort_order: sort_order,
        }, function() {
          this.getItems();
        }.bind(this));
      },
      handleSelectItem: function(id, is_checked) {
        var selected_ids = this.state.selected_ids,
            selection = false;

        if(is_checked) {
          selected_ids = jQuery.merge(selected_ids, [ id ]);
          // check whether all items on the page are selected
          if(
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
        if(is_checked === false) {
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
        if(this.state.selection === 'all') {
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
          this.getItems();
        }.bind(this));
      },
      handleGroup: function(group) {
        // reset search
        jQuery('#search_input').val('');

        this.setState({
          group: group,
          filter: [],
          search: '',
          page: 1
        }, function() {
          this.getItems();
        }.bind(this));
      },
      handleSetPage: function(page) {
        this.setState({
          page: page,
          selection: false,
          selected_ids: []
        }, function() {
          this.getItems();
        }.bind(this));
      },
      handleRenderItem: function(item, actions) {
        var render = this.props.onRenderItem(item, actions);
        return render.props.children;
      },
      handleRefreshItems: function() {
        this.getItems();
      },
      render: function() {
        var items = this.state.items,
            sort_by =  this.state.sort_by,
            sort_order =  this.state.sort_order;

        // bulk actions
        var bulk_actions = this.props.bulk_actions || [];

        if(this.state.group === 'trash') {
          bulk_actions = [
            {
              name: 'restore',
              label: 'Restore',
              onSuccess: this.props.messages.onRestore
            },
            {
              name: 'trash',
              label: 'Delete permanently',
              onSuccess: this.props.messages.onConfirmDelete,
              getData: function() {
                return { confirm: true };
              }
            }
          ];
        }

        // item actions
        var item_actions = this.props.item_actions || [];

        var tableClasses = classNames(
          'wp-list-table',
          'widefat',
          'fixed',
          'striped',
          { 'mailpoet_listing_loading': this.state.loading }
        );

        // search
        var search = (
          <ListingSearch
            onSearch={ this.handleSearch }
            search={ this.state.search }
          />
        );
        if(this.props.search === false) {
          search = false;
        }

        // groups
        var groups = (
          <ListingGroups
            groups={ this.state.groups }
            group={ this.state.group }
            onSelectGroup={ this.handleGroup }
          />
        );
        if(this.props.groups === false) {
          groups = false;
        }

        return (
          <div>
            { groups }
            { search }
            <div className="tablenav top clearfix">
              <ListingBulkActions
                bulk_actions={ bulk_actions }
                selection={ this.state.selection }
                selected_ids={ this.state.selected_ids }
                onBulkAction={ this.handleBulkAction } />
              <ListingFilters
                filters={ this.state.filters }
                filter={ this.state.filter }
                onSelectFilter={ this.handleFilter } />
              <ListingPages
                count={ this.state.count }
                page={ this.state.page }
                limit={ this.state.limit }
                onSetPage={ this.handleSetPage } />
            </div>
            <table className={ tableClasses }>
              <thead>
                <ListingHeader
                  onSort={ this.handleSort }
                  onSelectItems={ this.handleSelectItems }
                  selection={ this.state.selection }
                  sort_by={ this.state.sort_by }
                  sort_order={ this.state.sort_order }
                  columns={ this.props.columns }
                  is_selectable={ bulk_actions.length > 0 } />
              </thead>

              <ListingItems
                onRenderItem={ this.handleRenderItem }
                onDeleteItem={ this.handleDeleteItem }
                onRestoreItem={ this.handleRestoreItem }
                onRefreshItems={ this.handleRefreshItems }
                columns={ this.props.columns }
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
                  sort_by={ this.state.sort_by }
                  sort_order={ this.state.sort_order }
                  columns={ this.props.columns }
                  is_selectable={ bulk_actions.length > 0 } />
              </tfoot>

            </table>
            <div className="tablenav bottom">
              <ListingBulkActions
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

    return Listing;
  }
);
