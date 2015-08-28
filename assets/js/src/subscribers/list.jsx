define(
  'list',
  [
    'react',
    'jquery',
    'mailpoet',
    'listing/listing.jsx'
  ],
  function(
    React,
    jQuery,
    MailPoet,
    Listing
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
      render: function() {
        return (
          <Listing
            columns={columns}
            actions={actions} />
        );
      }
    });

    return List;
  }
);