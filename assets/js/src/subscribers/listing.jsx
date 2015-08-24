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
      render: function() {
        var order = '';
        if(this.props.column.sortable) {
          order = this.props.column.order || 'asc';
        }
        var classes = classNames(
          'manage-column',
          { 'sortable': this.props.column.sortable },
          order
        );

        return (
          <th
            className={ classes }
            id={ this.props.column.name }
            scope="col">{ this.props.column.label }</th>
        );
      }
    });

    var ListingHeader = React.createClass({
      render: function() {

        return (
          <tr>
            <td className="manage-column column-cb check-column" id="cb">
              <label htmlFor="cb-select-all-1" className="screen-reader-text">
                Select All
              </label>
              <input type="checkbox" id="cb-select-all-1" />
            </td>
            { this.props.columns.map(function(column) {
              return (<ListingColumn key={column.name} column={column} />);
            })}
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
                  <a
                    title="Edit “{ this.props.item.email }”"
                    href="#"
                    className="row-title">{ this.props.item.email }</a>
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
    });

    var Listing = React.createClass({
      getInitialState: function() {
        return {
          search: '',
          page: 1,
          limit: 10,
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
          data: {
            search: this.state.search
          },
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
        this.setState({ search: search }, function() {
          this.getItems();
        }.bind(this));
      },
      render: function() {
        return (
          <div>
            <ListingSearch onSearch={this.handleSearch} />
            <div className="tablenav top">

            </div>
            <table className="wp-list-table widefat fixed">
              <thead>
                <ListingHeader
                  columns={this.props.columns} />
              </thead>

              <ListingItems
                columns={this.props.columns}
                items={this.state.items} />

              <tfoot>
                <ListingHeader
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
        label: 'Status',
        sortable: true
      },
      {
        name: 'created_at',
        label: 'Subscribed on'
      },
      {
        name: 'updated_at',
        label: 'Last modified on'
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