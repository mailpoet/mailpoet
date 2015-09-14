define(
  [
    'react',
    'react-router',
    'listing/listing.jsx',
    'classnames',
  ],
  function(
    React,
    Router,
    Listing,
    classNames
  ) {
    var Link = Router.Link;

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
      renderItem: function(subscriber) {
        var rowClasses = classNames(
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
            <td className={ rowClasses }>
              <strong>
                <a>{ subscriber.email }</a>
              </strong>

              <div className="row-actions">
                <span className="edit">
                  <Link to="edit" params={{ id: subscriber.id }}>Edit</Link>
                </span>
              </div>

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
            endpoint="subscribers"
            onRenderItem={ this.renderItem }
            items={ this.getItems }
            columns={ columns }
            bulk_actions={ bulk_actions } />
        );
      }
    });

    return List;
  }
);