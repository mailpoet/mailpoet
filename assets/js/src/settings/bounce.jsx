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
    ];

    var messages = {
      updated: function() {
        MailPoet.Notice.success('Settings succesfully updated!');
      }
    };

    var SettingsForm = React.createClass({
      render: function() {

        return (
          <Form
            endpoint="settings"
            fields={ fields }
            params={ this.props.params }
            messages={ messages } />
        );
      }
    });

    return SettingsForm;
  }
);