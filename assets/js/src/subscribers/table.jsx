define('subscribers.table',
  ['mailpoet', 'jquery', 'react/addons', 'react-waypoint'],
  function(MailPoet, jQuery, React, Waypoint) {

    var InfiniteScrollExample = React.createClass({
      _loadMoreItems: function() {
        this.setState({ loading: true });

        this.setState({ page: (this.state.page + 1) }, function() {
          this.loadItems();
        }.bind(this));
      },
      loadItems: function() {
        MailPoet.Ajax.post({
          endpoint: 'subscribers',
          action: 'get',
          data: {
            offset: (this.state.page - 1) * this.state.limit,
            limit: this.state.limit
          },
          onSuccess: function(response) {
            if(this.isMounted()) {
              var items = jQuery.merge(this.state.items, response);
              this.setState({
                items: items,
                loading: false
              });
            }
          }.bind(this)
        });
      },
      componentDidMount: function() {
        this.loadItems();
      },
      getInitialState: function() {
        // set up list of items ...
        return {
          loading: false,
          items: [],
          page: 1,
          limit: 50
        };
      },

      _renderLoadingMessage: function() {
        if (this.state.loading) {
          return (
            <p>
              Loading {this.state.limit} more items
            </p>
          );
        } else {
          return (
            <p>{this.state.items.length} items</p>
          );
        }
      },

      _renderItems: function() {
        return this.state.items.map(function(subscriber, index) {
          return (
            <tr>
            <th className="check-column" scope="row">
              <label htmlFor="cb-select-1" className="screen-reader-text">
                Select { subscriber.email }</label>
              <input
                type="checkbox"
                value={ subscriber.id }
                name="item[]" id="cb-select-1" />
            </th>
            <td className="title column-title has-row-actions column-primary page-title">
              <strong>
                <a className="row-title">{ subscriber.email }</a>
              </strong>
            </td>
            <td>
              { subscriber.first_name }
            </td>
            <td>
              { subscriber.last_name }
            </td>
            <td className="date column-date">
              <abbr title="">{ subscriber.created_at }</abbr>
            </td>
            <td className="date column-date">
              <abbr title="">{ subscriber.updated_at }</abbr>
            </td>
          </tr>
          );
        });
      },

      _renderWaypoint: function() {
        if (!this.state.loading) {
          return (
            <Waypoint
              onEnter={this._loadMoreItems}
              threshold={0.8} />
          );
        }
      },

      render: function() {
        return (
          <div>
            {this._renderLoadingMessage()}
            <div className="infinite-scroll-example">
              <div className="infinite-scroll-example__scrollable-parent">
                {this._renderItems()}
                {this._renderWaypoint()}
              </div>
            </div>
          </div>
        );
      }
    });

    React.render(
      <InfiniteScrollExample />,
      document.getElementById('example')
    )
  }
);