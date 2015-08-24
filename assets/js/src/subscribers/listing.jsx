define('subscribers.listing',
  ['react/addons', 'jquery', 'mailpoet', 'classnames'],
  function(React, jQuery, MailPoet, classNames) {

    var ListingGroups = React.createClass({
      render: function() {
        return (
          <div></div>
        );
      }
    });

    var ListingSearch = React.createClass({
      handleSearch: function(e) {
        e.preventDefault();
        this.props.onSearch(
          this.refs.search.getDOMNode().value
        );
      },
      render: function() {
        return (
          <form name="search" onSubmit={this.handleSearch}>
            <p className="search-box">
              <label htmlFor="search_input" className="screen-reader-text">
                Search
              </label>
              <input
                type="search"
                ref="search"
                id="search_input" />
              <input
                type="submit"
                value="Search"
                className="button" />
            </p>
          </form>
        );
      }
    });

    var ListingFilters = React.createClass({
      render: function() {
        return (
          <div></div>
        );
      }
    });

    var ListingPages = React.createClass({
      render: function() {
        return (
          <div></div>
        );
      }
    });

    var ListingBulkActions = React.createClass({
      render: function() {
        return (
          <div></div>
        );
      }
    });

    var ListingColumn = React.createClass({
      handleSort: function() {
        var sort_by = this.props.column.name,
            sort_order = (this.props.column.sorted === 'asc') ? 'desc' : 'asc';
        this.props.onSort(sort_by, sort_order);
      },
      render: function() {
        var classes = classNames(
          'manage-column',
          { 'sortable': this.props.column.sortable },
          this.props.column.sorted
        );

        var label;

        if(this.props.column.sortable === true) {
          label = (
            <a onClick={this.handleSort}>
              <span>{ this.props.column.label }</span>
              <span className="sorting-indicator"></span>
            </a>
          );
        } else {
          label = this.props.column.label;
        }
        return (
          <th
            className={ classes }
            id={this.props.column.name }
            scope="col">
            {label}
          </th>
        );
      }
    });

    var ListingHeader = React.createClass({
      render: function() {
        var columns = this.props.columns.map(function(column) {
              return (
                <ListingColumn
                  onSort={this.props.onSort}
                  sort_by={this.props.sort_by}
                  key={column.name}
                  column={column} />
              );
        }.bind(this));

        return (
          <tr>
            <td className="manage-column column-cb check-column" id="cb">
              <label htmlFor="cb-select-all-1" className="screen-reader-text">
                Select All
              </label>
              <input type="checkbox" id="cb-select-all-1" />
            </td>
            {columns}
          </tr>
        );
      }
    });

    var ListingItem = React.createClass({
      render: function() {
        return (
          <tr>
            <th className="check-column" scope="row">
              <label htmlFor="cb-select-1" className="screen-reader-text">
                Select { this.props.item.email }</label>
              <input
                type="checkbox"
                value={ this.props.item.id }
                name="item[]" id="cb-select-1" />
            </th>
            <td className="title column-title has-row-actions column-primary page-title">
                <strong>
                  <a className="row-title">{ this.props.item.email }</a>
                </strong>
            </td>
            <td></td>
            <td className="date column-date">
              <abbr title="">{ this.props.item.created_at }</abbr>
            </td>
            <td className="date column-date">
              <abbr title="">{ this.props.item.updated_at }</abbr>
            </td>
          </tr>
        );
      }
    });

    var ListingItems = React.createClass({
      render: function() {
        if(this.props.items.length === 0) {
          return (
            <tbody>
              <td
                colSpan={this.props.columns.length + 1}
                className="colspanchange">No subscribers found.</td>
            </tbody>
          );
        } else {
          return (
            <tbody>
              {this.props.items.map(function(item) {
                return (
                  <ListingItem
                    columns={this.props.columns}
                    key={item.id}
                    item={item} />
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
          search: '',
          page: 1,
          limit: 10,
          sort_by: 'email',
          sort_order: 'asc',
          items: []
        };
      },
      componentDidMount: function() {
        this.getItems();
      },
      getItems: function() {
        MailPoet.Ajax.post({
          endpoint: 'subscribers',
          action: 'get',
          onSuccess: function(response) {
            if(this.isMounted()) {
              this.setState({
                items: response
              });
            }
          }.bind(this)
        });
      },
      handleSearch: function(search) {
        this.setState({ search: search });
      },
      handleSort: function(sort_by, sort_order = 'asc') {
        this.setState({
          sort_by: sort_by,
          sort_order: sort_order
        });
      },
      render: function() {
        var items = this.state.items,
            search = this.state.search.trim().toLowerCase(),
            sort_by =  this.state.sort_by,
            sort_order =  this.state.sort_order;

        // search
        if(search.length > 0) {
          items = items.filter(function(item){
            return item.email.toLowerCase().match(search);
          });
        }

        // sorting
        items = items.sort(function(a, b) {
          if(a[sort_by] === b[sort_by]) {
            return 0;
          } else {
            if(sort_order === 'asc') {
              return (a[sort_by] > b[sort_by]) ? 1 : -1;
            } else {
              return (a[sort_by] < b[sort_by]) ? 1 : -1;
            }
          }
        });

        columns = columns.map(function(column) {
          column.sorted = (column.name === sort_by) ? sort_order : false;
          return column;
        });

        return (
          <div>
            <ListingSearch onSearch={this.handleSearch} />
            <div className="tablenav top">

            </div>
            <table className="wp-list-table widefat fixed">
              <thead>
                <ListingHeader
                  onSort={this.handleSort}
                  sort_by={this.state.sort_by}
                  sort_order={this.state.sort_order}
                  columns={this.props.columns} />
              </thead>

              <ListingItems
                columns={this.props.columns}
                items={items} />

              <tfoot>
                <ListingHeader
                  onSort={this.handleSort}
                  sort_by={this.state.sort_by}
                  sort_order={this.state.sort_order}
                  columns={this.props.columns} />
              </tfoot>

            </table>
            <div className="tablenav bottom">

            </div>
          </div>
        );
      }
    });


    var columns = [
      {
        name: 'email',
        label: 'Email',
        sortable: true
      },
      {
        name: 'status',
        label: 'Status'
      },
      {
        name: 'created_at',
        label: 'Subscribed on',
        sortable: true
      },
      {
        name: 'updated_at',
        label: 'Last modified on',
        sortable: true
      },
    ];

    var element = jQuery('#mailpoet_subscribers_listing');

    if(element.length > 0) {
      React.render(
        <Listing columns={columns} />,
        element[0]
      );
    }
  }
);
/*
<ListingGroups />
<ListingSearch />
<ListingBulkActions />
<ListingFilters />
<ListingPages />

<ListingItems />

<ListingBulkActions />
<ListingPages />
*/