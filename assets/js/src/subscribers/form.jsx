define(
  [
    'react',
    'react-router',
    'jquery',
    'mailpoet',
    'classnames',
    'form/form.jsx'
  ],
  function(
    React,
    Router,
    jQuery,
    MailPoet,
    classNames,
    Form
  ) {

    var fields = [
      {
        name: 'email',
        label: 'E-mail'
      },
      {
        name: 'first_name',
        label: 'Firstname'
      },
      {
        name: 'last_name',
        label: 'Lastname'
      }
    ];

    var SubscriberForm = React.createClass({
      render: function() {

        return (
          <Form fields={ fields } params={ this.props.params } />
        );
      }
    });

    return SubscriberForm;
  }
);