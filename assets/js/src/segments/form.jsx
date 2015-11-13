define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx'
  ],
  function(
    React,
    Router,
    MailPoet,
    Form
  ) {

    var fields = [
      {
        name: 'name',
        label: 'Name',
        type: 'text'
      },
      {
        name: 'description',
        label: 'Description',
        type: 'textarea'
      }
    ];

    var messages = {
      onUpdate: function() {
        MailPoet.Notice.success('Segment successfully updated!');
      },
      onCreate: function() {
        MailPoet.Notice.success('Segment successfully added!');
      }
    };

    var SegmentForm = React.createClass({
      mixins: [
        Router.History
      ],
      render: function() {
        return (
          <div>
            <h2 className="title">
              Segment <a
                href="javascript:;"
                className="add-new-h2"
                onClick={ this.history.goBack }
              >Back to list</a>
            </h2>

            <Form
              endpoint="segments"
              fields={ fields }
              params={ this.props.params }
              messages={ messages }
            />
          </div>
        );
      }
    });

    return SegmentForm;
  }
);
