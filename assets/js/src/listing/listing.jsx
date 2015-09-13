define(
  [
    'mailpoet',
    'jquery',
    'react',
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
    classNames,
    ListingBulkActions,
    ListingHeader,
    ListingPages,
    ListingSearch,
    ListingGroups,
    ListingFilters
  ) {
     var ListingItem = React.createClass({
      handleSelectItem: function(e) {
        var is_checked = jQuery(e.target).is(':checked');

        this.props.onSelectItem(
          parseInt(e.target.value, 10),
          is_checked
        );

        return !e.target.checked;
      },
      render: function() {

        var checkbox = false;

        if(this.props.is_selectable === true) {
          checkbox = (
            <th className="mailpoet_check_column" scope="row">
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

        return (
          <tr>
            { checkbox }
            { this.props.onRenderItem(this.props.item) }
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
                  { MailPoetI18n.selectAllLabel }&nbsp;
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
                    selection={ this.props.selection }
                    is_selectable={ this.props.is_selectable }
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
          filters: [],
          selected_ids: [],
          selection: false
        };
      },
      componentDidMount: function() {
        this.getItems();
      },
      getItems: function() {
        this.setState({ loading: true });
        this.props.items.bind(null, this)();
      },
      handleSearch: function(search) {
        this.setState({
          search: search,
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
          this.clearSelection();
          this.getItems();
        }.bind(this));
      },
      handleSelectItem: function(id, is_checked) {
        var selected_ids = this.state.selected_ids;

        if(is_checked) {
          selected_ids = jQuery.merge(selected_ids, [ id ]);
        } else {
          selected_ids.splice(selected_ids.indexOf(id), 1);
        }

        this.setState({
          selection: false,
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
      handleGroup: function(group) {
        // reset search
        jQuery('#search_input').val('');

        this.setState({
          group: group,
          filters: [],
          search: '',
          page: 1
        }, function() {
          this.clearSelection();
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
      handleRenderItem: function(item) {
        return this.props.onRenderItem(item);
      },
      render: function() {
        var items = this.state.items,
            sort_by =  this.state.sort_by,
            sort_order =  this.state.sort_order;

        // set sortable columns
        columns = this.props.columns.map(function(column) {
          column.sorted = (column.name === sort_by) ? sort_order : false;
          return column;
        });

        // bulk actions
        var bulk_actions = this.props.bulk_actions || [];

        var tableClasses = classNames(
          'wp-list-table',
          'widefat',
          'fixed',
          'striped',
          { 'mailpoet_listing_loading': this.state.loading }
        );
        return (
          <div>
            <ListingGroups
              groups={ this.state.groups }
              group={ this.state.group }
              onSelectGroup={ this.handleGroup } />
            <ListingSearch
              onSearch={ this.handleSearch }
              search={ this.state.search } />
            <div className="tablenav top clearfix">
              <ListingBulkActions
                bulk_actions={ bulk_actions } />
              <ListingFilters filters={ this.state.filters } />
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
                columns={ this.props.columns }
                is_selectable={ bulk_actions.length > 0 }
                onSelectItem={ this.handleSelectItem }
                onSelectAll={ this.handleSelectAll }
                selected_ids={ this.state.selected_ids }
                selection={ this.state.selection }
                loading={ this.state.loading }
                count={ this.state.count }
                limit={ this.state.limit }
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
                bulk_actions={ bulk_actions } />
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
