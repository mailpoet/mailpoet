define(
  [
    'react',
    'listing/listing.jsx',
    'classnames'
  ],
  function(
    React,
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

    var bulk_actions = [
      {
        name: 'move',
        label: 'Move to list...'
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
      renderItem: function(subscriber, actions) {
        var row_classes = classNames(
          'manage-column',
          'column-primary',
          'has-row-actions'
        );

        var status = '';

        switch(subscriber.status) {
          case 'subscribed':
            status = 'Subscribed';
          break;

          case 'unconfirmed':
            status = 'Unconfirmed';
          break;

          case 'unsubscribed':
            status = 'Unsubscribed';
          break;
        }

        return (
          <div>
            <td className={ row_classes }>
              <strong>
                <a>{ subscriber.email }</a>
              </strong>
              { actions }
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
            endpoint="subscribers"
            onRenderItem={ this.renderItem }
            columns={ columns }
            bulk_actions={ bulk_actions } />
        );
      }
    });

    return List;
  }
);