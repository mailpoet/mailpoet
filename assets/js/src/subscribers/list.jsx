define(
  [
    'react',
    'react-router',
    'listing/listing.jsx',
    'form/fields/selection.jsx',
    'classnames',
    'mailpoet',
    'jquery',
    'select2'
  ],
  function(
    React,
    Router,
    Listing,
    Selection,
    classNames,
    MailPoet,
    jQuery
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
        name: 'segments',
        label: 'Lists',
        sortable: false
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

    var messages = {
      onDelete: function(response) {
        var count = ~~response.subscribers;
        var message = null;

        if(count === 1) {
          message = (
            '1 subscriber was moved to the trash.'
          ).replace('%$1d', count);
        } else if(count > 1) {
          message = (
            '%$1d subscribers were moved to the trash.'
          ).replace('%$1d', count);
        }

        if(message !== null) {
          MailPoet.Notice.success(message);
        }
      },
      onConfirmDelete: function(response) {
        var count = ~~response.subscribers;
        var message = null;

        if(count === 1) {
          message = (
            '1 subscriber was permanently deleted.'
          ).replace('%$1d', count);
        } else if(count > 1) {
          message = (
            '%$1d subscribers were permanently deleted.'
          ).replace('%$1d', count);
        }

        if(message !== null) {
          MailPoet.Notice.success(message);
        }
      },
      onRestore: function(response) {
        var count = ~~response.subscribers;
        var message = null;

        if(count === 1) {
          message = (
            '1 subscriber has been restored from the trash.'
          ).replace('%$1d', count);
        } else if(count > 1) {
          message = (
            '%$1d subscribers have been restored from the trash.'
          ).replace('%$1d', count);
        }

        if(message !== null) {
          MailPoet.Notice.success(message);
        }
      }
    };

    var bulk_actions = [
      {
        name: 'moveToList',
        label: 'Move to list...',
        onSelect: function() {
          var field = {
            id: 'move_to_segment',
            endpoint: 'segments'
          };

          return (
            <Selection field={ field }/>
          );
        },
        getData: function() {
          return {
            segment_id: ~~(jQuery('#move_to_segment').val())
          }
        },
        onSuccess: function(response) {
          MailPoet.Notice.success(
            '%$1d subscribers were moved to list <strong>%$2s</strong>.'
            .replace('%$1d', ~~response.subscribers)
            .replace('%$2s', response.segment)
          );
        }
      },
      {
        name: 'addToList',
        label: 'Add to list...',
        onSelect: function() {
          var field = {
            id: 'add_to_segment',
            endpoint: 'segments'
          };

          return (
            <Selection field={ field }/>
          );
        },
        getData: function() {
          return {
            segment_id: ~~(jQuery('#add_to_segment').val())
          }
        },
        onSuccess: function(response) {
          MailPoet.Notice.success(
            '%$1d subscribers were added to list <strong>%$2s</strong>.'
            .replace('%$1d', ~~response.subscribers)
            .replace('%$2s', response.segment)
          );
        }
      },
      {
        name: 'removeFromList',
        label: 'Remove from list...',
        onSelect: function() {
          var field = {
            id: 'remove_from_segment',
            endpoint: 'segments'
          };

          return (
            <Selection field={ field }/>
          );
        },
        getData: function() {
          return {
            segment_id: ~~(jQuery('#remove_from_segment').val())
          }
        },
        onSuccess: function(response) {
          MailPoet.Notice.success(
            '%$1d subscribers were removed from list <strong>%$2s</strong>.'
            .replace('%$1d', ~~response.subscribers)
            .replace('%$2s', response.segment)
          );
        }
      },
      {
        name: 'removeFromAllLists',
        label: 'Remove from all lists',
        onSuccess: function(response) {
          MailPoet.Notice.success(
            '%$1d subscribers were removed from all lists.'
            .replace('%$1d', ~~response.subscribers)
            .replace('%$2s', response.segment)
          );
        }
      },
      {
        name: 'confirmUnconfirmed',
        label: 'Confirm unconfirmed',
        onSuccess: function(response) {
          MailPoet.Notice.success(
            '%$1d subscribers have been confirmed.'
            .replace('%$1d', ~~response.subscribers)
          );
        }
      },
      {
        name: 'trash',
        label: 'Trash',
        getData: function() {
          return {
            confirm: false
          }
        },
        onSuccess: messages.onDelete
      }
    ];

    var SubscriberList = React.createClass({
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

        var segments = mailpoet_segments.filter(function(segment) {
          return (jQuery.inArray(segment.id, subscriber.segments) !== -1);
        }).map(function(segment) {
          return segment.name;
        }).join(', ');

        return (
          <div>
            <td className={ row_classes }>
              <strong><Link to={ `/edit/${ subscriber.id }` }>
                { subscriber.email }
              </Link></strong>
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
            <td className="column" data-colname="Lists">
              { segments }
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
          <div>
            <h2 className="title">
              Subscribers <Link className="add-new-h2" to="/new">New</Link>
            </h2>

            <Listing
              endpoint="subscribers"
              onRenderItem={ this.renderItem }
              columns={ columns }
              bulk_actions={ bulk_actions }
              messages={ messages }
            />
          </div>
        );
      }
    });

    return SubscriberList;
  }
);