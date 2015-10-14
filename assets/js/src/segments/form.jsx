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
        name: 'name',
        label: 'Name',
        type: 'text'
      }
    ];

    var messages = {
      updated: function() {
        MailPoet.Notice.success('Segment successfully updated!');
      },
      created: function() {
        MailPoet.Notice.success('Segment successfully added!');
      }
    };

    var SegmentForm = React.createClass({
      render: function() {

        return (
          <Form
            endpoint="segments"
            fields={ fields }
            params={ this.props.params }
            messages={ messages } />
        );
      }
    });

    return SegmentForm;
  }
);
