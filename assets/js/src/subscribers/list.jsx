define(
  'list',
  [
    'react',
    'jquery',
    'mailpoet',
    'listing/listing.jsx',
    'classnames'
  ],
  function(
    React,
    jQuery,
    MailPoet,
    Listing,
    classNames
  ) {

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
        name: 'status',
        label: 'Status',
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
        name: 'move',
        label: 'Move to list...',
        onSelect: function(e) {
          // display list selector
          jQuery(e.target).after(
            '<select id="bulk_action_list">'+
              '<option value="">Select a list</option>'+
              '<option value="1">List #1</option>'+
              '<option value="2">List #2</option>'+
              '<option value="3">List #3</option>'+
            '</select>'
          );
        },
        onApply: function(selected) {
          var list = jQuery('#bulk_action_list').val();

          MailPoet.Ajax.post({
            endpoint: 'subscribers',
            action: 'move',
            data: {
              selected: selected,
              list: list
            }
          });
        }
      },
      {
        name: 'add',
        label: 'Add to list...'
      },
      {
        name: 'remove',
        label: 'Remove from list...'
      }
    ];

    var List = React.createClass({
      getItems: function(listing) {
        MailPoet.Ajax.post({
          endpoint: 'subscribers',
          action: 'get',
          data: {
            offset: (listing.state.page - 1) * listing.state.limit,
            limit: listing.state.limit,
            group: listing.state.group,
            search: listing.state.search,
            sort_by: listing.state.sort_by,
            sort_order: listing.state.sort_order
          },
          onSuccess: function(response) {
            if(listing.isMounted()) {
              listing.setState({
                items: response.items || [],
                filters: response.filters || [],
                groups: response.groups || [],
                count: response.count || 0,
                loading: false
              });
            }
          }.bind(listing)
        });
      },
      renderItem: function(subscriber) {
        var rowClasses = classNames(
          'manage-column',
          'column-primary',
          'has-row-actions'
        );

        var status = '';

        switch(parseInt(subscriber.status, 10)) {
          case 1:
            status = 'Subscribed';
          break;

          case 0:
            status = 'Unconfirmed';
          break;

          case -1:
            status = 'Unsubscribed';
          break;
        }

        return (
          <div>
            <td className={ rowClasses }>
              <strong>
                <a>{ subscriber.email }</a>
              </strong>
              <button className="toggle-row" type="button">
                <span className="screen-reader-text">Show more details</span>
              </button>
            </td>
            <td className="column" data-colname="First name">
              { subscriber.first_name }
            </td>
            <td className="column" data-colname="Last name">
              { subscriber.last_name }
            </td>
            <td className="column" data-colname="Status">
              { status }
            </td>
            <td className="column-date" data-colname="Subscribed on">
              <abbr>{ subscriber.created_at }</abbr>
            </td>
            <td className="column-date" data-colname="Last modified on">
              <abbr>{ subscriber.updated_at }</abbr>
            </td>
          </div>
        );
      },
      render: function() {
        return (
          <Listing
            onRenderItem={this.renderItem}
            items={this.getItems}
            columns={columns}
            actions={actions} />
        );
      }
    });

    return List;
  }
);