define('subscribers.listing',
  ['mailpoet', 'jquery', 'react/addons', 'classnames'],
  function(MailPoet, jQuery, React, classNames) {

    var ListingSearch = require('listing/search.jsx');
    var ListingPages = require('listing/pages.jsx');
    var ListingColumn = require('listing/column.jsx');
    var ListingHeader = require('listing/header.jsx');

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

    var ListingFilters = React.createClass({
      render: function() {
        return (
          <div></div>
        );
      }
    });

    var ListSelector = React.createClass({
      getList: function(e) {
        e.preventDefault();
        MailPoet.Modal.popup({
          title: 'Bulk action',
          template: '',
          onInit: function(modal) {
            var target = modal.getContentContainer();
            React.render(
              <ListSelector />,
              target[0]
            );
          }
        })
      },
      render: function() {
        return (
          <div>
            <select >
              <option>Select a list</option>
            </select>
            <a onClick={this.getList}>Test me</a>
          </div>
        );
      }
    });

    var ListingBulkAction = React.createClass({
      render: function() {
        return (
          <option value={this.props.action.action}>
            { this.props.action.label }
          </option>
        );
      }
    });

    var ListingBulkActions = React.createClass({
      handleAction: function() {
        var action = jQuery(this.refs.action.getDOMNode()).val();
        console.log(action, this.props.selected);

      },
      render: function() {
        return (
          <div className="alignleft actions bulkactions">
            <label
              className="screen-reader-text"
              htmlFor="bulk-action-selector-top">
              Select bulk action
            </label>

            <select ref="action">
              <option>Bulk Actions</option>
              {this.props.actions.map(function(action, index) {
                return (
                  <ListingBulkAction
                    action={action}
                    key={index} />
                );
              }.bind(this))}
            </select>
            <input
              onClick={this.handleAction}
              type="submit"
              defaultValue="Apply"
              className="button action" />
          </div>
        );
      }
    });

    var ListingItem = React.createClass({
      handleSelect: function(e) {
        var is_checked = jQuery(e.target).is(':checked');

        this.props.onSelect(
          parseInt(e.target.value, 10),
          is_checked
        );

        return !e.target.checked;
      },
      render: function() {
        var rowClasses = classNames(
          'title',
          'column-title',
          'has-row-actions',
          'column-primary',
          'page-title'
        );

        return (
          <tr>
            <th className="check-column" scope="row">
              <label className="screen-reader-text">
                { 'Select ' + this.props.item.email }</label>
              <input
                type="checkbox"
                defaultValue={ this.props.item.id }
                defaultChecked={ this.props.item.selected }
                onChange={ this.handleSelect } />
            </th>
            <td className={rowClasses}>
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
                item.selected = (this.props.selected.indexOf(item.id) !== -1);
                return (
                  <ListingItem
                    columns={this.props.columns}
                    onSelect={this.props.onSelect}
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
          items: [],
          selected: []
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
      handleSelect: function(id, is_checked) {
        var selected = this.state.selected;

        if(is_checked) {
          selected = jQuery.merge(selected, [ id ]);
        } else {
          selected.splice(selected.indexOf(id), 1);
        }
        this.setState({
          selected: selected
        });
      },
      handleSelectAll: function(is_checked) {
        if(is_checked === false) {
          this.setState({ selected: [] });
        } else {
          var selected = this.state.items.map(function(item) {
            return ~~item.id;
          });

          this.setState({
            selected: selected
          });
        }
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
              <ListingBulkActions
                actions={this.props.actions}
                selected={this.state.selected} />
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
                  onSelectAll={this.handleSelectAll}
                  sort_by={this.state.sort_by}
                  sort_order={this.state.sort_order}
                  columns={this.props.columns} />
              </thead>

              <ListingItems
                columns={this.props.columns}
                selected={this.state.selected}
                onSelect={this.handleSelect}
                items={items} />

              <tfoot>
                <ListingHeader
                  onSort={this.handleSort}
                  onSelectAll={this.handleSelectAll}
                  sort_by={this.state.sort_by}
                  sort_order={this.state.sort_order}
                  columns={this.props.columns} />
              </tfoot>

            </table>
            <div className="tablenav bottom">
              <ListingBulkActions
                actions={this.props.actions}
                selected={this.state.selected} />
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

    var actions = [
      {
        label: 'Move to list...',
        endpoint: 'subscribers',
        action: 'move',

      },
      {
        label: 'Add to list...',
        endpoint: 'subscribers',
        action: 'add',

      },
      {
        label: 'Remove from list...',
        endpoint: 'subscribers',
        action: 'remove',

      }
    ];

    var element = jQuery('#mailpoet_subscribers_listing');

    if(element.length > 0) {
      React.render(
        <Listing columns={columns} actions={actions} />,
        element[0]
      );
    }
  }
);