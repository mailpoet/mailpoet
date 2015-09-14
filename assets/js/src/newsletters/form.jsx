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
        name: 'subject',
        label: 'Subject',
        type: 'text'
      },
      {
        name: 'body',
        label: 'Body',
        type: 'textarea'
      }
    ];

    var messages = {
      updated: function() {
        MailPoet.Notice.success('Newsletter succesfully updated!');
      },
      created: function() {
        MailPoet.Notice.success('Newsletter succesfully added!');
      }
    };

    var NewsletterForm = React.createClass({
      render: function() {

        return (
          <Form
            endpoint="newsletters"
            fields={ fields }
            params={ this.props.params }
            messages={ messages } />
        );
      }
    });

    return NewsletterForm;
  }
);