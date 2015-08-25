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

      _renderItems: function() {
        return this.state.items.map(function(subscriber, index) {
          return (
            <p key={index}>{subscriber.email}</p>
          );
        });
      },

      _renderWaypoint: function() {
        if (!this.state.loading) {
          return (
            <Waypoint
              onEnter={this._loadMoreItems}
              threshold={2.0} />
          );
        }
      },

      render: function() {
        //MailPoet.Modal.loading(this.state.loading);
        return (
          <div>
            <p>{this.state.items.length} items</p>
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