define(
  [
    'react',
    'mailpoet',
    'form/form.jsx'
  ],
  function(
    React,
    MailPoet,
    Form
  ) {

    var fields = [
      {
        name: 'email',
        label: 'E-mail',
        type: 'text'
      },
      {
        name: 'first_name',
        label: 'Firstname',
        type: 'text'
      },
      {
        name: 'last_name',
        label: 'Lastname',
        type: 'text'
      },
      {
        name: 'status',
        label: 'Status',
        type: 'select',
        values: {
          'subscribed': 'Subscribed',
          'unconfirmed': 'Unconfirmed',
          'unsubscribed': 'Unsubscribed'
        }
      }
    ];

    var messages = {
      updated: function() {
        MailPoet.Notice.success('Subscriber succesfully updated!');
      },
      created: function() {
        MailPoet.Notice.success('Subscriber succesfully added!');
      }
    };

    var SubscriberForm = React.createClass({
      render: function() {

        return (
          <Form
            endpoint="subscribers"
            fields={ fields }
            params={ this.props.params }
            messages={ messages } />
        );
      }
    });

    return SubscriberForm;
  }
);