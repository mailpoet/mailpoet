define('subscribers.listing',
  ['mailpoet', 'jquery', 'react/addons', 'classnames'],
  function(MailPoet, jQuery, React, classNames) {

    var ListingGroups = React.createClass({
      render: function() {
        return (
          <ul className="subsubsub">
            <li>
              <a className="current">
                All
                <span className="count">({ this.props.count })</span>
              </a>&nbsp;|&nbsp;
            </li>
            <li>
              <a>
                Subscribed
                <span className="count">(0)</span>
              </a>
            </li>
          </ul>
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
                id="search_input"
                defaultValue={this.props.search} />
              <input
                type="submit"
                defaultValue="Search"
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
      setFirstPage: function() {
        this.props.onSetPage(1);
      },
      setLastPage: function() {
        this.props.onSetPage(this.getLastPage());
      },
      setPreviousPage: function() {
        this.props.onSetPage(this.constrainPage(this.props.page - 1));
      },
      setNextPage: function() {
        this.props.onSetPage(this.constrainPage(this.props.page + 1));
      },
      constrainPage: function(page) {
        return Math.min(Math.max(1, Math.abs(~~page)), this.getLastPage());
      },
      handleSetPage: function() {
        this.props.onSetPage(
          this.constrainPage(this.refs.page.getDOMNode().value)
        );
      },
      getLastPage: function() {
        return Math.ceil(this.props.count / this.props.limit);
      },
      render: function() {
        if(this.props.count === 0) {
          return (<div></div>);
        } else {
          var pagination,
          firstPage = (
            <span aria-hidden="true" className="tablenav-pages-navspan">«</span>
          ),
          previousPage = (
            <span aria-hidden="true" className="tablenav-pages-navspan">‹</span>
          ),
          nextPage = (
            <span aria-hidden="true" className="tablenav-pages-navspan">›</span>
          ),
          lastPage = (
            <span aria-hidden="true" className="tablenav-pages-navspan">»</span>
          );

          if(this.props.count > this.props.limit) {
            if(this.props.page > 1) {
              previousPage = (
                <a href="javascript:;"
                  onClick={this.setPreviousPage}
                  className="prev-page">
                  <span className="screen-reader-text">Previous page</span>
                  <span aria-hidden="true">‹</span>
                </a>
              );
            }

            if(this.props.page > 2) {
              firstPage = (
                <a href="javascript:;"
                  onClick={this.setFirstPage}
                  className="first-page">
                  <span className="screen-reader-text">First page</span>
                  <span aria-hidden="true">«</span>
                </a>
              );
            }

            if(this.props.page < this.getLastPage()) {
              nextPage = (
                <a href="javascript:;"
                  onClick={this.setNextPage}
                  className="next-page">
                  <span className="screen-reader-text">Next page</span>
                  <span aria-hidden="true">›</span>
                </a>
              );
            }

            if(this.props.page < this.getLastPage() - 1) {
              lastPage = (
                <a href="javascript:;"
                  onClick={this.setLastPage}
                  className="last-page">
                  <span className="screen-reader-text">Last page</span>
                  <span aria-hidden="true">»</span>
                </a>
              );
            }

            pagination = (
              <span className="pagination-links">
                {firstPage}
                {previousPage}
                &nbsp;
                <span className="paging-input">
                  <label
                    className="screen-reader-text"
                    htmlFor="current-page-selector">Current Page</label>
                  <input
                    type="text"
                    onChange={this.handleSetPage}
                    aria-describedby="table-paging"
                    size="1"
                    ref="page"
                    value={this.props.page}
                    name="paged"
                    id="current-page-selector"
                    className="current-page" />
                  &nbsp;of&nbsp;
                  <span className="total-pages">
                    {Math.ceil(this.props.count / this.props.limit)}
                  </span>
                </span>
                &nbsp;
                {nextPage}
                {lastPage}
              </span>
            );
          }

          var classes = classNames(
            'tablenav-pages',
            { 'one-page': (this.props.count <= this.props.limit) }
          );

          return (
            <div className={classes}>
              <span className="displaying-num">{ this.props.count } item(s)</span>
              { pagination }
            </div>
          );
        }
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
                defaultValue={ this.props.item.id }
                name="item[]" id="cb-select-1" />
            </th>
            <td className="title column-title has-row-actions column-primary page-title">
              <strong>
                <a className="row-title">{ this.props.item.email }</a>
              </strong>
            </td>
            <td>
              { this.props.item.first_name }
            </td>
            <td>
              { this.props.item.last_name }
            </td>
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
          loading: false,
          search: '',
          page: 1,
          count: 0,
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
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: 'subscribers',
          action: 'get',
          data: {
            offset: (this.state.page - 1) * this.state.limit,
            limit: this.state.limit,
            search: this.state.search,
            sort_by: this.state.sort_by,
            sort_order: this.state.sort_order
          },
          onSuccess: function(response) {
            if(this.isMounted()) {
              this.setState({
                items: response.items,
                count: response.count,
                loading: false
              });
            }
          }.bind(this)
        });
      },
      handleSearch: function(search) {
        this.setState({ search: search }, function() {
          this.getItems();
        }.bind(this));
      },
      handleSort: function(sort_by, sort_order = 'asc') {
        this.setState({
          sort_by: sort_by,
          sort_order: sort_order
        }, function() {
          this.getItems();
        }.bind(this));
      },
      handleSetPage: function(page) {
        this.setState({ page: page }, function() {
          this.getItems();
        }.bind(this));
      },
      render: function() {
        var items = this.state.items,
            sort_by =  this.state.sort_by,
            sort_order =  this.state.sort_order;

        // set sortable columns
        columns = columns.map(function(column) {
          column.sorted = (column.name === sort_by) ? sort_order : false;
          return column;
        });

        var tableClasses = classNames(
          'wp-list-table',
          'widefat',
          'fixed',
          'striped',
          { 'mailpoet_listing_loading': this.state.loading }
        );

        return (
          <div>
            <ListingGroups count={this.state.count} />
            <ListingSearch
              onSearch={this.handleSearch}
              search={this.state.search} />
            <div className="tablenav top clearfix">
              <ListingBulkActions />
              <ListingFilters />
              <ListingPages
                count={this.state.count}
                page={this.state.page}
                limit={this.state.limit}
                onSetPage={this.handleSetPage} />
            </div>
            <table className={tableClasses}>
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
              <ListingBulkActions />
              <ListingPages
                count={this.state.count}
                page={this.state.page}
                limit={this.state.limit}
                onSetPage={this.handleSetPage} />
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
        name: 'first_name',
        label: 'Firstname',
        sortable: true
      },
      {
        name: 'last_name',
        label: 'Lastname',
        sortable: true
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